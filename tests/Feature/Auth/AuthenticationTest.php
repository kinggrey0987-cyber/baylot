<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginOtpMail;
use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    // -----------------------------------------------------------------------
    // OTP ENABLED (default / production behaviour)
    // -----------------------------------------------------------------------

    public function test_valid_credentials_redirect_to_two_factor_challenge(): void
    {
        config(['auth.otp_enabled' => true]);
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // User must NOT be authenticated yet — waiting for OTP verification.
        $this->assertGuest();
        $response->assertRedirect(route('two-factor.show'));
        $response->assertSessionHas('two_factor_user_id', $user->id);
        Mail::assertSent(LoginOtpMail::class, 1);
        $this->assertSame(1, LoginOtp::query()->where('user_id', $user->id)->count());
    }

    public function test_login_returns_clean_error_when_otp_email_delivery_fails(): void
    {
        config(['auth.otp_enabled' => true]);

        $user = User::factory()->create();

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable'));

        $response = $this
            ->from(route('login'))
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('two_factor_user_id');
        $this->assertSame(0, LoginOtp::query()->where('user_id', $user->id)->count());
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsVerified($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    // -----------------------------------------------------------------------
    // OTP DISABLED (dev / testing bypass)
    // -----------------------------------------------------------------------

    public function test_login_bypasses_otp_and_authenticates_immediately_when_otp_disabled(): void
    {
        config(['auth.otp_enabled' => false]);

        $user = User::factory()->create(['role' => 'agent']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertSessionHas('two_factor_verified', true);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_buyer_login_bypasses_otp_and_redirects_to_home_when_otp_disabled(): void
    {
        config(['auth.otp_enabled' => false]);

        $buyer = User::factory()->create(['role' => 'buyer']);

        $response = $this->post('/login', [
            'email' => $buyer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($buyer);
        $response->assertSessionHas('two_factor_verified', true);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_invalid_credentials_still_rejected_when_otp_disabled(): void
    {
        config(['auth.otp_enabled' => false]);

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
