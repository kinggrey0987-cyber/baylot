<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listing_id');
            $table->uuid('buyer_id');
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'claimed', 'closed', 'cancelled'])->default('new');
            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('listings')->cascadeOnDelete();
            $table->foreign('buyer_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('lead_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id')->unique();
            $table->uuid('agent_id');
            $table->timestamp('claimed_at');
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->uuid('assigned_agent_id')->nullable();
            $table->timestamp('requested_start');
            $table->timestamp('requested_end');
            $table->enum('status', ['pending', 'approved', 'rescheduled', 'completed', 'cancelled', 'rejected'])->default('pending');
            $table->text('agent_notes')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('assigned_agent_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('lead_claims');
        Schema::dropIfExists('leads');
    }
};
