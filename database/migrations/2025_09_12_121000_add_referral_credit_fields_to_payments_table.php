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
            // Referral credit fields
            $table->integer('referral_credits_used')->default(0)->after('refunded_amount'); // Amount of referral credits used in cents
            $table->integer('final_amount')->default(0)->after('referral_credits_used'); // Final amount charged after credits in cents
            
            // Update payment method enum to include referral credits
            $table->dropIndex(['payment_method']);
        });
        
        // Update payment method enum to include referral_credits
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('stripe', 'paypal', 'cash', 'referral_credits') DEFAULT 'stripe'");
        
        Schema::table('payments', function (Blueprint $table) {
            // Recreate index
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['referral_credits_used', 'final_amount']);
            $table->dropIndex(['payment_method']);
        });
        
        // Revert payment method enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('stripe', 'paypal', 'cash') DEFAULT 'stripe'");
        
        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_method');
        });
    }
};