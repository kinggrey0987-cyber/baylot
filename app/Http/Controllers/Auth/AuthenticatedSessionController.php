<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        if (! Auth::validate($request->only('email', 'password'))) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        $user = User::query()->where('email', $request->string('email'))->firstOrFail();

        // Dev/testing bypass: when OTP is disabled, log in immediately without
        // issuing or sending an OTP. The two_factor_verified session flag is set
        // so the EnsureTwoFactorVerified middleware passes normally.
        if (! config('auth.otp_enabled', true)) {
            $remember = $request->boolean('remember');
            Auth::loginUsingId($user->id, $remember);
            $request->session()->regenerate();
            $request->session()->put('two_factor_verified', true);

            $fallbackRoute = $user->role === 'buyer'
                ? route('home', absolute: false)
                : route('dashboard', absolute: false);

            return redirect()->intended($fallbackRoute);
        }

        app(TwoFactorController::class)->issueAndSendOtp($user);

        Auth::guard('web')->logout();
        $request->session()->forget('two_factor_verified');
        $request->session()->put('two_factor_user_id', $user->id);
        $request->session()->put('two_factor_remember', $request->boolean('remember'));
        $request->session()->regenerateToken();

        return redirect()->route('two-factor.show');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
