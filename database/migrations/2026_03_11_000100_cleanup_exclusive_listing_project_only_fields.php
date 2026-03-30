<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('listings')
            ->where('listing_type', 'exclusive')
            ->update([
                'price_max' => null,
                'lot_area_max_sqm' => null,
                'bedrooms_max' => null,
                'bathrooms_max' => null,
                'floors_max' => null,
                'available_units' => null,
            ]);
    }

    public function down(): void
    {
        // Irreversible data cleanup.
    }
};
