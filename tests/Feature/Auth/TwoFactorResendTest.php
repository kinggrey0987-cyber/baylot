<?php

namespace Tests\Feature\Auth;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TwoFactorResendTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_is_blocked_during_cooldown_window(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        LoginOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now()->subSeconds(20),
        ]);

        $response = $this
            ->withSession([
                'two_factor_user_id' => $user->id,
                'two_factor_remember' => false,
            ])
            ->post(route('two-factor.resend'));

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, LoginOtp::query()->count());
    }

    public function test_resend_is_blocked_when_hourly_limit_is_reached(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        foreach ([59, 48, 36, 24, 12] as $minutesAgo) {
            LoginOtp::query()->create([
                'user_id' => $user->id,
                'code_hash' => Hash::make((string) random_int(100000, 999999)),
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
                'last_sent_at' => now()->subMinutes($minutesAgo),
            ]);
        }

        $response = $this
            ->withSession([
                'two_factor_user_id' => $user->id,
                'two_factor_remember' => false,
            ])
            ->post(route('two-factor.resend'));

        $response->assertSessionHasErrors('code');
        $this->assertSame(5, LoginOtp::query()->count());
    }

    public function test_successful_resend_invalidates_previous_unconsumed_codes(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        LoginOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now()->subMinutes(3),
        ]);

        $response = $this
            ->withSession([
                'two_factor_user_id' => $user->id,
                'two_factor_remember' => false,
            ])
            ->post(route('two-factor.resend'));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertSame(2, LoginOtp::query()->count());
        $this->assertSame(1, LoginOtp::query()->whereNull('consumed_at')->count());
    }
}
