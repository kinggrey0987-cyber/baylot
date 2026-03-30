<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@baylot.local'],
            ['name' => 'BayLot Admin', 'password' => 'password', 'role' => 'admin', 'broker_id' => null, 'is_active' => true]
        );

        $brokerOne = User::updateOrCreate(
            ['email' => 'broker1@baylot.local'],
            ['name' => 'Broker One Realty', 'password' => 'password', 'role' => 'broker', 'broker_id' => null, 'is_active' => true]
        );

        $brokerTwo = User::updateOrCreate(
            ['email' => 'broker2@baylot.local'],
            ['name' => 'Broker Two Estates', 'password' => 'password', 'role' => 'broker', 'broker_id' => null, 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'agent1@baylot.local'],
            ['name' => 'Agent One', 'password' => 'password', 'role' => 'agent', 'broker_id' => $brokerOne->id, 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'agent2@baylot.local'],
            ['name' => 'Agent Two', 'password' => 'password', 'role' => 'agent', 'broker_id' => $brokerOne->id, 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'agent3@baylot.local'],
            ['name' => 'Agent Three', 'password' => 'password', 'role' => 'agent', 'broker_id' => $brokerTwo->id, 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'buyer@baylot.local'],
            ['name' => 'Demo Buyer', 'password' => 'password', 'role' => 'buyer', 'broker_id' => null, 'is_active' => true]
        );
    }
}
