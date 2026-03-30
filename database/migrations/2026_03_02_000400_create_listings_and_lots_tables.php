<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('listing_type', ['shared', 'exclusive']);
            $table->uuid('project_id')->nullable();
            $table->uuid('owner_agent_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2);
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('owner_agent_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_hybrid_rule_check CHECK ((listing_type = 'shared' AND project_id IS NOT NULL AND owner_agent_id IS NULL) OR (listing_type = 'exclusive' AND owner_agent_id IS NOT NULL AND project_id IS NULL))");

        DB::statement('ALTER TABLE listings ADD COLUMN location geometry(Point, 4326) NOT NULL');
        DB::statement('CREATE INDEX listings_location_gist_idx ON listings USING GIST (location)');

        Schema::create('listing_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listing_id');
            $table->string('image_url');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('listings')->cascadeOnDelete();
        });

        Schema::create('lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listing_id');
            $table->string('lot_code');
            $table->decimal('area_sqm', 12, 2)->nullable();
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('listings')->cascadeOnDelete();
            $table->unique(['listing_id', 'lot_code']);
        });

        DB::statement('ALTER TABLE lots ADD COLUMN geom geometry(Geometry, 4326) NOT NULL');
        DB::statement('CREATE INDEX lots_geom_gist_idx ON lots USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('listings');
    }
};
