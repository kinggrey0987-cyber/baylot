<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $constraints = DB::select(<<<'SQL'
                SELECT conname
                FROM pg_constraint c
                INNER JOIN pg_class t ON t.oid = c.conrelid
                WHERE t.relname = 'users'
                    AND c.contype = 'c'
                    AND pg_get_constraintdef(c.oid) ILIKE '%role%'
            SQL);

            foreach ($constraints as $constraint) {
                $name = str_replace('"', '""', $constraint->conname);
                DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS \"{$name}\"");
            }

            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'agent', 'broker', 'buyer'))");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'agent', 'broker', 'buyer') NOT NULL DEFAULT 'buyer'");
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'broker')
            ->update(['role' => 'agent']);

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $constraints = DB::select(<<<'SQL'
                SELECT conname
                FROM pg_constraint c
                INNER JOIN pg_class t ON t.oid = c.conrelid
                WHERE t.relname = 'users'
                    AND c.contype = 'c'
                    AND pg_get_constraintdef(c.oid) ILIKE '%role%'
            SQL);

            foreach ($constraints as $constraint) {
                $name = str_replace('"', '""', $constraint->conname);
                DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS \"{$name}\"");
            }

            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'agent', 'buyer'))");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'agent', 'buyer') NOT NULL DEFAULT 'buyer'");
        }
    }
};
