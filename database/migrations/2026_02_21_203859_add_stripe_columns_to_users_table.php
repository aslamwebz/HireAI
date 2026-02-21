<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->unique();
            $table->string('plan')->default('free');
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price');
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stripe_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_id',
                'plan',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
                'subscription_ends_at',
            ]);
        });
    }
};
