<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('created_by_admin_id');
            $table->timestamps();

            $table->foreign('created_by_admin_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('project_agents', function (Blueprint $table) {
            $table->uuid('project_id');
            $table->uuid('agent_id');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('users')->cascadeOnDelete();
            $table->primary(['project_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_agents');
        Schema::dropIfExists('projects');
    }
};
