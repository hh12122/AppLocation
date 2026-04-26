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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('rental_id')->after('id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->after('rental_id')->constrained()->onDelete('cascade');

            $table->string('status')->default('pending')->after('payment_method');

            // Amounts in cents
            $table->integer('amount')->after('status');
            $table->integer('platform_fee')->default(0)->after('amount');
            $table->integer('gateway_fee')->default(0)->after('platform_fee');
            $table->integer('owner_payout')->default(0)->after('gateway_fee');
            $table->integer('refunded_amount')->default(0)->after('owner_payout');
            $table->integer('referral_credits_used')->default(0)->after('refunded_amount');
            $table->integer('final_amount')->default(0)->after('referral_credits_used');

            $table->string('currency', 3)->default('EUR')->after('final_amount');

            // Payment gateway references
            $table->string('stripe_payment_intent_id')->nullable()->after('currency');
            $table->string('stripe_charge_id')->nullable()->after('stripe_payment_intent_id');
            $table->string('stripe_refund_id')->nullable()->after('stripe_charge_id');
            $table->string('paypal_order_id')->nullable()->after('stripe_refund_id');
            $table->string('paypal_capture_id')->nullable()->after('paypal_order_id');
            $table->string('paypal_refund_id')->nullable()->after('paypal_capture_id');

            // Additional payment details
            $table->json('payment_details')->nullable()->after('paypal_refund_id');
            $table->json('metadata')->nullable()->after('payment_details');

            // Important dates
            $table->timestamp('paid_at')->nullable()->after('metadata');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->timestamp('failed_at')->nullable()->after('refunded_at');

            $table->string('failure_reason')->nullable()->after('failed_at');
            $table->text('notes')->nullable()->after('failure_reason');

            // Indexes
            $table->index('status');
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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['rental_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'rental_id',
                'user_id',
                'status',
                'amount',
                'platform_fee',
                'gateway_fee',
                'owner_payout',
                'refunded_amount',
                'referral_credits_used',
                'final_amount',
                'currency',
                'stripe_payment_intent_id',
                'stripe_charge_id',
                'stripe_refund_id',
                'paypal_order_id',
                'paypal_capture_id',
                'paypal_refund_id',
                'payment_details',
                'metadata',
                'paid_at',
                'refunded_at',
                'failed_at',
                'failure_reason',
                'notes',
            ]);
        });
    }
};
