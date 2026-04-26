<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payable_type')->nullable()->after('id');
            $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type');

            $table->index(['payable_type', 'payable_id']);
        });

        // Backfill existing rental payment records
        DB::statement("UPDATE payments SET payable_type = 'App\\\\Models\\\\Rental', payable_id = rental_id WHERE rental_id IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payable_type', 'payable_id']);
            $table->dropColumn(['payable_type', 'payable_id']);
        });
    }
};
