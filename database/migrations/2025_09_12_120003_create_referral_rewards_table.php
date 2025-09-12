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
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('referral_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('reward_type', ['credit', 'discount', 'bonus', 'milestone'])->default('credit');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'awarded', 'expired', 'used'])->default('pending');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_in_rental_id')->nullable()->constrained('rentals')->nullOnDelete();
            $table->json('conditions')->nullable(); // Usage conditions
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};