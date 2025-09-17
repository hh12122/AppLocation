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
        Schema::table('reviews', function (Blueprint $table) {
            // Add polymorphic columns
            $table->unsignedBigInteger('reviewable_id')->nullable()->after('reviewee_id');
            $table->string('reviewable_type')->nullable()->after('reviewable_id');

            // If you had a vehicle_id column before, drop it
            if (Schema::hasColumn('reviews', 'vehicle_id')) {
                $table->dropColumn('vehicle_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['reviewable_id', 'reviewable_type']);

            // If you want to rollback, you can add vehicle_id back
            $table->unsignedBigInteger('vehicle_id')->nullable();
        });
    }
};
