<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DemoDatabaseResetSeeder
 *
 * Truncates all demo-related tables in dependency order so that legacy rows
 * from prior development runs do not pollute a fresh demo dataset.
 *
 * Use cases:
 *   - Demo preparation before a client presentation
 *   - QA reset between test cycles
 *   - Removing stale rows written by earlier development seeders
 *
 * Safety guards:
 *   - Only runs in local / testing environments.
 *   - Does NOT drop or alter any schema (tables, columns, keys stay intact).
 *   - Does NOT touch GeofenceSeeder data (geofences table is not touched).
 *   - Does NOT modify ownership logic, OTP logic, or any application code.
 *
 * Production:
 *   Running this seeder in production will throw a RuntimeException and abort
 *   before touching any data.
 */
class DemoDatabaseResetSeeder extends Seeder
{
    /**
     * Truncation order respects foreign-key dependencies (children first).
     * CASCADE is used as a safety net for any constraint we missed.
     */
    private const TRUNCATE_ORDER = [
        'wishlists',
        'appointments',
        'lead_claims',
        'leads',
        'listing_images',
        'lots',
        'listings',
        'project_agents',
        'projects',
        'notifications',
        'login_otps',
        'password_reset_tokens',
        'sessions',
        'users',
    ];

    public function run(): void
    {
        // Hard production guard — never wipe data outside local/testing.
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException(
                'DemoDatabaseResetSeeder must not run in a production environment.'
            );
        }

        $this->command?->info('Resetting demo database tables...');

        // Disable FK checks for the duration of the truncation block.
        // We use a single TRUNCATE ... CASCADE per table rather than
        // SET session_replication_role because that requires superuser on some
        // managed PostgreSQL hosts.
        DB::transaction(function (): void {
            foreach (self::TRUNCATE_ORDER as $table) {
                DB::statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");
                $this->command?->line("  ✓ Truncated: {$table}");
            }
        });

        $this->command?->info('Demo tables cleared. Ready for re-seeding.');
    }
}
