<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        // When OTP/2FA is globally disabled (dev/testing only), bypass the session check.
        // This is intentional: OTP_ENABLED=false is never set in production.
        if (! config('auth.otp_enabled', true)) {
            return $next($request);
        }

        if (Auth::check() && ! $request->session()->has('two_factor_verified')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your session requires two-factor verification. Please log in again.',
            ]);
        }

        return $next($request);
    }
}
