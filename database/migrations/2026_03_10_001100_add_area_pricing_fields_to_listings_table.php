<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->decimal('lot_area_sqm', 12, 2)->nullable();
            $table->decimal('floor_area_sqm', 12, 2)->nullable();
            $table->decimal('price_per_sqm', 14, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['lot_area_sqm', 'floor_area_sqm', 'price_per_sqm']);
        });
    }
};
