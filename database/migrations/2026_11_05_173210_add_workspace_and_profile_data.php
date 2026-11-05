<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->json('workspace_data')->nullable();
            $table->unsignedInteger('workspace_version')->default(0);
            $table->string('verification_status')->default('pending');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['workspace_data', 'workspace_version', 'verification_status']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
