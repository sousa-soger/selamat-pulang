<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', fn (Blueprint $table) => $table->longText('training_data')->nullable());
    }

    public function down(): void
    {
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn('training_data'));
    }
};
