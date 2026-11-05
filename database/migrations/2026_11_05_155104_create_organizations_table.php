<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('subscription_status')->default('pending');
            $table->boolean('admin_provisioned')->default(false);
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_checkout_id')->nullable();
            $table->text('stripe_checkout_url')->nullable();
            $table->timestamp('checkout_expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
