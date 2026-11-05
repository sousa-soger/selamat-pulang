<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user');
            $table->string('google_id')->nullable()->unique();
            $table->string('mykkp_id')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('certificate_status')->default('not_submitted');
            $table->text('certificate_feedback')->nullable();
            $table->timestamp('certificate_submitted_at')->nullable();
            $table->timestamp('certificate_reviewed_at')->nullable();
            $table->foreignId('certificate_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['certificate_reviewed_by']);
            $table->dropColumn(['role', 'google_id', 'mykkp_id', 'certificate_path', 'certificate_status', 'certificate_feedback', 'certificate_submitted_at', 'certificate_reviewed_at', 'certificate_reviewed_by']);
        });
    }
};
