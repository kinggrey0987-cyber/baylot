<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GeofenceSeeder::class,
            DemoDatabaseResetSeeder::class,
            DemoUserSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
