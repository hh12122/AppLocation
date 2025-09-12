<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade');
            $table->string('referral_code', 20);
            $table->enum('status', ['pending', 'completed', 'expired'])->default('pending');
            $table->enum('conversion_type', ['registration', 'first_rental', 'first_listing'])->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->decimal('reward_amount', 10, 2)->default(0);
            $table->boolean('reward_paid')->default(false);
            $table->timestamp('reward_paid_at')->nullable();
            $table->json('metadata')->nullable(); // Additional tracking data
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
            $table->index(['referred_user_id']);
            $table->index(['referral_code']);
            $table->unique(['referrer_id', 'referred_user_id']); // One referral per user pair
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};