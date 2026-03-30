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
            [
                'name' => 'BayLot Technical Admin',
                'bio' => null,
                'password' => 'password',
                'role' => 'admin',
                'broker_id' => null,
                'is_active' => true,
                'phone' => null,
                'profile_photo_path' => null,
                'email_verified_at' => now(),
            ]
        );

        $brokers = [];
        foreach ([
            [
                'email' => 'broker1@baylot.local',
                'name' => 'Northpoint Realty Bacolod',
                'bio' => 'Full-service brokerage focused on curated shared inventory in Bacolod and northern Negros corridors.',
                'phone' => '+63 917 111 1001',
                'photo' => '/images/logo.png',
            ],
            [
                'email' => 'broker2@baylot.local',
                'name' => 'MetroWest Homes Negros',
                'bio' => 'Broker team specializing in mixed residential offerings from Silay to Victorias growth clusters.',
                'phone' => '+63 917 111 1002',
                'photo' => '/images/logo.png',
            ],
            [
                'email' => 'broker3@baylot.local',
                'name' => 'Sugarland Prime Estates',
                'bio' => 'Investment-oriented brokerage managing project listings in Bago and San Carlos urbanizing belts.',
                'phone' => '+63 917 111 1003',
                'photo' => '/images/logo.png',
            ],
            [
                'email' => 'broker4@baylot.local',
                'name' => 'South Coast Premier Realty',
                'bio' => 'Client-first brokerage handling premium and entry-level property portfolios across south Negros.',
                'phone' => '+63 917 111 1004',
                'photo' => '/images/logo.png',
            ],
        ] as $brokerData) {
            $brokers[$brokerData['email']] = User::updateOrCreate(
                ['email' => $brokerData['email']],
                [
                    'name' => $brokerData['name'],
                    'bio' => $brokerData['bio'],
                    'password' => 'password',
                    'role' => 'broker',
                    'broker_id' => null,
                    'is_active' => true,
                    'phone' => $brokerData['phone'],
                    'profile_photo_path' => $brokerData['photo'],
                    'email_verified_at' => now(),
                ]
            );
        }

        foreach ([
            ['email' => 'agent1@baylot.local', 'name' => 'Alyssa Cortez', 'broker_email' => 'broker1@baylot.local', 'is_active' => true, 'phone' => '+63 917 201 1001'],
            ['email' => 'agent2@baylot.local', 'name' => 'Marco Villarin', 'broker_email' => 'broker1@baylot.local', 'is_active' => true, 'phone' => '+63 917 201 1002'],
            ['email' => 'agent3@baylot.local', 'name' => 'Nina Tolentino', 'broker_email' => 'broker1@baylot.local', 'is_active' => false, 'phone' => '+63 917 201 1003'],
            ['email' => 'agent4@baylot.local', 'name' => 'Ralph Sarmiento', 'broker_email' => 'broker2@baylot.local', 'is_active' => true, 'phone' => '+63 917 202 1004'],
            ['email' => 'agent5@baylot.local', 'name' => 'Jessa Montealto', 'broker_email' => 'broker2@baylot.local', 'is_active' => true, 'phone' => '+63 917 202 1005'],
            ['email' => 'agent6@baylot.local', 'name' => 'Paolo Arceo', 'broker_email' => 'broker2@baylot.local', 'is_active' => true, 'phone' => '+63 917 202 1006'],
            ['email' => 'agent7@baylot.local', 'name' => 'Shane Dela Pena', 'broker_email' => 'broker3@baylot.local', 'is_active' => true, 'phone' => '+63 917 203 1007'],
            ['email' => 'agent8@baylot.local', 'name' => 'Ian Cuenca', 'broker_email' => 'broker3@baylot.local', 'is_active' => false, 'phone' => '+63 917 203 1008'],
            ['email' => 'agent9@baylot.local', 'name' => 'Mika Albao', 'broker_email' => 'broker3@baylot.local', 'is_active' => true, 'phone' => '+63 917 203 1009'],
            ['email' => 'agent10@baylot.local', 'name' => 'Kurt Jamora', 'broker_email' => 'broker4@baylot.local', 'is_active' => true, 'phone' => '+63 917 204 1010'],
            ['email' => 'agent11@baylot.local', 'name' => 'Trisha Magallon', 'broker_email' => 'broker4@baylot.local', 'is_active' => true, 'phone' => '+63 917 204 1011'],
            ['email' => 'agent12@baylot.local', 'name' => 'Noel Villafuerte', 'broker_email' => 'broker4@baylot.local', 'is_active' => true, 'phone' => '+63 917 204 1012'],
        ] as $agentData) {
            $brokerId = $brokers[$agentData['broker_email']]->id ?? null;

            if (! $brokerId) {
                continue;
            }

            User::updateOrCreate(
                ['email' => $agentData['email']],
                [
                    'name' => $agentData['name'],
                    'bio' => 'Licensed property specialist serving '.$brokers[$agentData['broker_email']]->name.' across priority listing zones in Negros Occidental.',
                    'password' => 'password',
                    'role' => 'agent',
                    'broker_id' => $brokerId,
                    'is_active' => $agentData['is_active'],
                    'phone' => $agentData['phone'],
                    'profile_photo_path' => '/images/logo.png',
                    'email_verified_at' => now(),
                ]
            );
        }

        foreach ([
            ['email' => 'buyer@baylot.local', 'name' => 'Demo Buyer', 'is_active' => true],
            ['email' => 'buyer2@baylot.local', 'name' => 'Maria Gonzaga', 'is_active' => true],
            ['email' => 'buyer3@baylot.local', 'name' => 'Lito Camacho', 'is_active' => true],
            ['email' => 'buyer4@baylot.local', 'name' => 'Kristine Abarico', 'is_active' => true],
            ['email' => 'buyer5@baylot.local', 'name' => 'Ramon Divinagracia', 'is_active' => false],
        ] as $buyerData) {
            User::updateOrCreate(
                ['email' => $buyerData['email']],
                [
                    'name' => $buyerData['name'],
                    'bio' => null,
                    'password' => 'password',
                    'role' => 'buyer',
                    'broker_id' => null,
                    'is_active' => $buyerData['is_active'],
                    'phone' => null,
                    'profile_photo_path' => null,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
