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
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->unique()->nullable()->after('is_admin');
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            $table->decimal('referral_credits', 10, 2)->default(0)->after('referred_by');
            $table->integer('referral_count')->default(0)->after('referral_credits');
            $table->timestamp('referral_code_generated_at')->nullable()->after('referral_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'referral_code',
                'referred_by',
                'referral_credits',
                'referral_count',
                'referral_code_generated_at'
            ]);
        });
    }
};