<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RequestedAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'rienz1213123@gmail.com'],
            [
                'name' => 'BayLot Admin',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::driver('bcrypt')->make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'rienz123123@gmail.com'],
            [
                'name' => 'BayLot Admin',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::driver('bcrypt')->make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'rienyhulleza0528@gmail.com'],
            [
                'name' => 'BayLot Buyer',
                'role' => 'buyer',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::driver('bcrypt')->make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'kinggrey0987@gmail.com'],
            [
                'name' => 'BayLot Agent',
                'role' => 'agent',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::driver('bcrypt')->make('password'),
            ]
        );
    }
}
