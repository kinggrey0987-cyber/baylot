<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('broker_id')->nullable()->after('role');
            $table->foreign('broker_id')->references('id')->on('users')->nullOnDelete();
            $table->index('broker_id');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->uuid('broker_id')->nullable()->after('description');
            $table->foreign('broker_id')->references('id')->on('users')->nullOnDelete();
            $table->index('broker_id');
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE projects ALTER COLUMN created_by_admin_id DROP NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE projects MODIFY created_by_admin_id CHAR(36) NULL');
        }

        $firstBrokerId = DB::table('users')
            ->where('role', 'broker')
            ->orderBy('created_at')
            ->value('id');

        if ($firstBrokerId) {
            DB::table('users')
                ->where('role', 'agent')
                ->whereNull('broker_id')
                ->update([
                    'broker_id' => $firstBrokerId,
                    'updated_at' => now(),
                ]);
        }

        $projectBrokerMappings = DB::table('project_agents')
            ->join('users', 'users.id', '=', 'project_agents.agent_id')
            ->where('users.role', 'broker')
            ->select('project_agents.project_id', DB::raw('MIN(project_agents.agent_id::text) AS broker_id'))
            ->groupBy('project_agents.project_id')
            ->get();

        foreach ($projectBrokerMappings as $mapping) {
            DB::table('projects')
                ->where('id', $mapping->project_id)
                ->whereNull('broker_id')
                ->update([
                    'broker_id' => $mapping->broker_id,
                    'updated_at' => now(),
                ]);
        }

        if ($firstBrokerId) {
            DB::table('projects')
                ->whereNull('broker_id')
                ->update([
                    'broker_id' => $firstBrokerId,
                    'updated_at' => now(),
                ]);
        }

        DB::table('project_agents')
            ->whereIn('agent_id', function ($query): void {
                $query->select('id')
                    ->from('users')
                    ->where('role', 'broker');
            })
            ->delete();
    }

    public function down(): void
    {
        $fallbackOwnerId = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('created_at')
            ->value('id')
            ?? DB::table('users')->orderBy('created_at')->value('id');

        if ($fallbackOwnerId) {
            DB::table('projects')
                ->whereNull('created_by_admin_id')
                ->update([
                    'created_by_admin_id' => $fallbackOwnerId,
                    'updated_at' => now(),
                ]);
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE projects ALTER COLUMN created_by_admin_id SET NOT NULL');
        } elseif ($driver === 'mysql' && $fallbackOwnerId) {
            DB::statement('ALTER TABLE projects MODIFY created_by_admin_id CHAR(36) NOT NULL');
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropForeign(['broker_id']);
            $table->dropIndex(['broker_id']);
            $table->dropColumn('broker_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['broker_id']);
            $table->dropIndex(['broker_id']);
            $table->dropColumn('broker_id');
        });
    }
};
