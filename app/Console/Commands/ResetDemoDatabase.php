<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDatabaseResetSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Console\Command;

/**
 * demo:reset
 *
 * Wipes all demo-related tables and re-seeds a clean, deterministic demo
 * dataset without touching the database schema.
 *
 * Use cases:
 *   - Quickly restore a clean demo environment before a presentation
 *   - Remove legacy rows from earlier development runs
 *   - QA reset between test cycles
 *
 * Usage:
 *   php artisan demo:reset            — interactive (asks for confirmation)
 *   php artisan demo:reset --force    — non-interactive (CI / scripts)
 *
 * Production guard:
 *   The underlying DemoDatabaseResetSeeder throws a RuntimeException when the
 *   app environment is not local or testing, so this command is safe to keep
 *   deployed without special firewall rules around it.
 */
class ResetDemoDatabase extends Command
{
    protected $signature = 'demo:reset
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Wipe demo tables and re-seed a clean demo dataset (local/testing only)';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('demo:reset may only run in local or testing environments.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This will wipe all demo tables and re-seed. Continue?')) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        $this->info('Step 1/3 — Clearing demo tables...');
        $this->call('db:seed', ['--class' => DemoDatabaseResetSeeder::class]);

        $this->info('Step 2/3 — Seeding demo users...');
        $this->call('db:seed', ['--class' => DemoUserSeeder::class]);

        $this->info('Step 3/3 — Seeding demo data...');
        $this->call('db:seed', ['--class' => DemoDataSeeder::class]);

        $this->newLine();
        $this->info('Demo dataset rebuilt successfully.');

        return self::SUCCESS;
    }
}
