<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->string('committee_role')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropColumn('committee_role');
        });
    }
};
