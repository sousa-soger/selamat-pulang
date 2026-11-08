<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('organization_user')->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Some users have multiple organizations. Resolve their memberships before applying this migration.');
        }
        Schema::table('organization_user', fn (Blueprint $table) => $table->unique('user_id'));
    }

    public function down(): void
    {
        Schema::table('organization_user', fn (Blueprint $table) => $table->dropUnique(['user_id']));
    }
};
