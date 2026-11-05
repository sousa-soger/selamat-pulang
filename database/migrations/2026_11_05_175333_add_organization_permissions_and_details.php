<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_create_organization')->default(true);
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('address')->nullable();
            $table->string('registration')->nullable();
            $table->string('industry')->nullable();
            $table->string('site')->nullable();
            $table->unsignedInteger('employees')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('can_create_organization'));
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn(['address', 'registration', 'industry', 'site', 'employees', 'phone', 'email']));
    }
};
