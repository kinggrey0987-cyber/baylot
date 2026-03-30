<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->decimal('price_max', 14, 2)->nullable();
            $table->decimal('lot_area_max_sqm', 12, 2)->nullable();
            $table->unsignedSmallInteger('bathrooms_max')->nullable();
            $table->unsignedSmallInteger('floors_max')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['price_max', 'lot_area_max_sqm', 'bathrooms_max', 'floors_max']);
        });
    }
};
