<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedInteger('available_units')->nullable();
        });

        DB::statement('ALTER TABLE listings DROP CONSTRAINT IF EXISTS listings_status_check');
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_status_check CHECK (status IN ('available', 'reserved', 'sold', 'unavailable'))");
    }

    public function down(): void
    {
        DB::statement("UPDATE listings SET status = 'sold' WHERE status = 'unavailable'");
        DB::statement('ALTER TABLE listings DROP CONSTRAINT IF EXISTS listings_status_check');
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_status_check CHECK (status IN ('available', 'reserved', 'sold'))");

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('available_units');
        });
    }
};
