<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 10;
    private const OTP_MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const RESEND_MAX_PER_HOUR = 5;

    public function show(Request $request): View|RedirectResponse
    {
        $pendingUser = $this->pendingUser($request);

        if (! $pendingUser) {
            return redirect()->route('login');
        }

        $resendState = $this->resendState($pendingUser);
        $hasIssuedOtp = LoginOtp::query()
            ->where('user_id', $pendingUser->id)
            ->exists();

        return view('auth.two-factor', [
            'maskedEmail' => $this->maskEmail($pendingUser->email),
            'otpSent' => $hasIssuedOtp,
            'remainingCooldown' => $resendState['cooldown_remaining_seconds'],
            'remainingHourlyLimit' => $resendState['hourly_limit_remaining_seconds'],
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pendingUser = $this->pendingUser($request);

        if (! $pendingUser) {
            throw ValidationException::withMessages([
                'code' => 'Your verification session expired. Please log in again.',
            ]);
        }

        /** @var LoginOtp|null $otp */
        $otp = LoginOtp::query()
            ->where('user_id', $pendingUser->id)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => 'No active verification code found. Please resend a new code.',
            ]);
        }

        if ($otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'This verification code has expired. Please request a new code.',
            ]);
        }

        if ($otp->attempts >= self::OTP_MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Too many invalid attempts. Please request a new code.',
            ]);
        }

        if (! Hash::check($validated['code'], $otp->code_hash)) {
            $otp->increment('attempts');

            if ($otp->fresh()->attempts >= self::OTP_MAX_ATTEMPTS) {
                throw ValidationException::withMessages([
                    'code' => 'Verification locked after 5 failed attempts. Please resend a new code.',
                ]);
            }

            throw ValidationException::withMessages([
                'code' => 'Invalid verification code.',
            ]);
        }

        $otp->forceFill([
            'consumed_at' => now(),
        ])->save();

        Auth::loginUsingId($pendingUser->id, (bool) $request->session()->pull('two_factor_remember', false));
        $request->session()->regenerate();
        $request->session()->put('two_factor_verified', true);
        $request->session()->forget('two_factor_user_id');
        $request->session()->forget('two_factor_otp_sent_at');

        $fallbackRoute = $pendingUser->role === 'buyer'
            ? route('home', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($fallbackRoute);
    }

    public function resend(Request $request): RedirectResponse
    {
        $pendingUser = $this->pendingUser($request);

        if (! $pendingUser) {
            return redirect()->route('login')->withErrors([
                'code' => 'Your verification session expired. Please log in again.',
            ]);
        }

        $resendState = $this->resendState($pendingUser);

        if ($resendState['cooldown_remaining_seconds'] > 0) {
            $remaining = $resendState['cooldown_remaining_seconds'];

            return back()->withErrors([
                'code' => "Please wait {$remaining} seconds before requesting another code.",
            ]);
        }

        if ($resendState['hourly_limit_remaining_seconds'] > 0) {
            $remaining = $resendState['hourly_limit_remaining_seconds'];
            $minutes = (int) ceil($remaining / 60);

            return back()->withErrors([
                'code' => "Resend limit reached. Please try again in about {$minutes} minute(s).",
            ]);
        }

        $this->issueAndSendOtp($pendingUser);

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    public function issueAndSendOtp(User $user): void
    {
        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Invalidate previous unconsumed codes so only the newest code can be used.
        LoginOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        LoginOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        Mail::to($user->email)->send(new LoginOtpMail($user, $plainCode, self::OTP_EXPIRY_MINUTES));
    }

    private function pendingUser(Request $request): ?User
    {
        $pendingUserId = $request->session()->get('two_factor_user_id');

        if (! $pendingUserId) {
            return null;
        }

        return User::query()->find($pendingUserId);
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email);
        $visible = mb_substr($name, 0, 1);
        $maskedName = $visible.str_repeat('*', max(1, mb_strlen($name) - 1));

        return $maskedName.'@'.$domain;
    }

    /**
     * @return array{cooldown_remaining_seconds:int, hourly_limit_remaining_seconds:int}
     */
    private function resendState(User $user): array
    {
        $now = now();

        $lastSentOtp = LoginOtp::query()
            ->where('user_id', $user->id)
            ->latest('last_sent_at')
            ->first(['last_sent_at']);

        $cooldownRemainingSeconds = 0;
        if ($lastSentOtp) {
            $secondsSinceLastSend = $lastSentOtp->last_sent_at->diffInSeconds($now);
            $cooldownRemainingSeconds = max(0, self::RESEND_COOLDOWN_SECONDS - $secondsSinceLastSend);
        }

        $sentInLastHour = LoginOtp::query()
            ->where('user_id', $user->id)
            ->where('last_sent_at', '>=', $now->copy()->subHour())
            ->orderBy('last_sent_at')
            ->get(['last_sent_at']);

        $hourlyLimitRemainingSeconds = 0;
        if ($sentInLastHour->count() >= self::RESEND_MAX_PER_HOUR) {
            $unlockAt = $sentInLastHour->first()->last_sent_at->copy()->addHour();
            $hourlyLimitRemainingSeconds = max(0, $unlockAt->timestamp - $now->timestamp);
        }

        return [
            'cooldown_remaining_seconds' => $cooldownRemainingSeconds,
            'hourly_limit_remaining_seconds' => $hourlyLimitRemainingSeconds,
        ];
    }
}
