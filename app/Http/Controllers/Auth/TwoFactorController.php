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

        $otpSentAt = $request->session()->get('two_factor_otp_sent_at');
        $hasActiveOtp = ! empty($otpSentAt);
        $remainingCooldown = 0;
        if ($hasActiveOtp) {
            $remainingCooldown = max(0, self::RESEND_COOLDOWN_SECONDS - now()->diffInSeconds($otpSentAt));
        }

        return view('auth.two-factor', [
            'maskedEmail' => $this->maskEmail($pendingUser->email),
            'otpSent' => $hasActiveOtp,
            'remainingCooldown' => $remainingCooldown,
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

        /** @var LoginOtp|null $latestOtp */
        $latestOtp = LoginOtp::query()
            ->where('user_id', $pendingUser->id)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();

        if ($latestOtp && now()->diffInSeconds($latestOtp->last_sent_at) < self::RESEND_COOLDOWN_SECONDS) {
            $remaining = (int) max(0, ceil(self::RESEND_COOLDOWN_SECONDS - now()->floatDiffInSeconds($latestOtp->last_sent_at)));

            return back()->withErrors([
                'code' => "Please wait {$remaining} seconds before resending another code.",
            ]);
        }

        $sentInLastHour = LoginOtp::query()
            ->where('user_id', $pendingUser->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($sentInLastHour >= self::RESEND_MAX_PER_HOUR) {
            return back()->withErrors([
                'code' => 'Resend limit reached (5 per hour). Please try again later.',
            ]);
        }

        $this->issueAndSendOtp($pendingUser);
        $request->session()->put('two_factor_otp_sent_at', now()->toDateTimeString());

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    public function issueAndSendOtp(User $user): void
    {
        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

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
}
