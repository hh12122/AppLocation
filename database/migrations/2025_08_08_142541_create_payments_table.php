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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who made the payment
            $table->enum('payment_method', ['stripe', 'paypal', 'cash'])->default('stripe');
            $table->enum('status', [
                'pending',
                'processing', 
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'partially_refunded'
            ])->default('pending');
            
            // Amounts in cents to avoid floating point issues
            $table->integer('amount'); // Total amount in cents
            $table->integer('platform_fee')->default(0); // Platform commission in cents
            $table->integer('gateway_fee')->default(0); // Payment gateway fee in cents
            $table->integer('owner_payout')->default(0); // Amount to be paid to owner in cents
            $table->integer('refunded_amount')->default(0); // Amount refunded in cents
            
            $table->string('currency', 3)->default('EUR');
            
            // Payment gateway references
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_refund_id')->nullable();
            $table->string('paypal_order_id')->nullable();
            $table->string('paypal_capture_id')->nullable();
            $table->string('paypal_refund_id')->nullable();
            
            // Additional payment details
            $table->json('payment_details')->nullable(); // Store additional payment data
            $table->json('metadata')->nullable(); // Custom metadata
            
            // Important dates
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            $table->string('failure_reason')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status');
            $table->index('payment_method');
            $table->index(['rental_id', 'status']);
            $table->index('stripe_payment_intent_id');
            $table->index('paypal_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};