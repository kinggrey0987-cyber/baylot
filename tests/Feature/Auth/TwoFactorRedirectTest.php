<?php

namespace Tests\Feature\Auth;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_is_redirected_to_home_when_no_intended_url_is_present_after_successful_two_factor_verification(): void
    {
        $buyer = User::factory()->create([
            'role' => 'buyer',
        ]);

        LoginOtp::query()->create([
            'user_id' => $buyer->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'two_factor_user_id' => $buyer->id,
                'two_factor_remember' => false,
            ])
            ->post(route('two-factor.verify'), [
                'code' => '123456',
            ]);

        $this->assertAuthenticatedAs($buyer);
        $response->assertRedirect(route('home'));
    }

    public function test_buyer_is_redirected_to_intended_url_after_successful_two_factor_verification(): void
    {
        $buyer = User::factory()->create([
            'role' => 'buyer',
        ]);

        LoginOtp::query()->create([
            'user_id' => $buyer->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'two_factor_user_id' => $buyer->id,
                'two_factor_remember' => false,
                'url.intended' => route('properties.index'),
            ])
            ->post(route('two-factor.verify'), [
                'code' => '123456',
            ]);

        $this->assertAuthenticatedAs($buyer);
        $response->assertRedirect(route('properties.index'));
    }

    public function test_agent_is_redirected_to_dashboard_after_successful_two_factor_verification(): void
    {
        $agent = User::factory()->create([
            'role' => 'agent',
        ]);

        LoginOtp::query()->create([
            'user_id' => $agent->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'two_factor_user_id' => $agent->id,
                'two_factor_remember' => false,
            ])
            ->post(route('two-factor.verify'), [
                'code' => '123456',
            ]);

        $this->assertAuthenticatedAs($agent);
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
