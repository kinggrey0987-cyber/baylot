<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new \RuntimeException('BayLot requires PostgreSQL with PostGIS. Set DB_CONNECTION=pgsql in .env and configure your PostgreSQL credentials before running migrations.');
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP EXTENSION IF EXISTS postgis');
    }
};
