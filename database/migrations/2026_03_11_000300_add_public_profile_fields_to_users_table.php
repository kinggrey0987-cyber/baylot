<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('bio')->nullable()->after('name');
            $table->string('phone', 40)->nullable()->after('email');
            $table->string('profile_photo_path')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['bio', 'phone', 'profile_photo_path']);
        });
    }
};
