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
        Schema::table('rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('rentals', 'payment_status')) {
                $table->enum('payment_status', [
                    'not_required',
                    'pending',
                    'paid',
                    'partially_paid',
                    'refunded',
                    'failed'
                ])->default('pending')->after('status');
            }
            
            if (!Schema::hasColumn('rentals', 'deposit_amount')) {
                $table->integer('deposit_amount')->default(0)->after('total_price'); // Security deposit in cents
            }
            
            if (!Schema::hasColumn('rentals', 'deposit_returned')) {
                $table->boolean('deposit_returned')->default(false)->after('deposit_amount');
            }
            
            if (!Schema::hasColumn('rentals', 'deposit_returned_at')) {
                $table->timestamp('deposit_returned_at')->nullable()->after('deposit_returned');
            }
        });
        
        // Add index separately
        if (!Schema::hasColumn('rentals', 'payment_status')) {
            Schema::table('rentals', function (Blueprint $table) {
                $table->index('payment_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'deposit_amount',
                'deposit_returned',
                'deposit_returned_at'
            ]);
        });
    }
};