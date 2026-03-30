<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Act as the given user with the two_factor_verified session flag already set.
     * Required when testing routes protected by the EnsureTwoFactorVerified middleware.
     */
    protected function actingAsVerified(User $user, ?string $guard = null): static
    {
        return $this->actingAs($user, $guard)->withSession(['two_factor_verified' => true]);
    }
}
