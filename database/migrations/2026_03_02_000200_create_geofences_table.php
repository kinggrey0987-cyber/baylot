<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE geofences ADD COLUMN geom geometry(MultiPolygon, 4326) NOT NULL');
        DB::statement('CREATE INDEX geofences_geom_gist_idx ON geofences USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
