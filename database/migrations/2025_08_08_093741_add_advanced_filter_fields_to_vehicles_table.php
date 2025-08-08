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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('vehicle_type')->nullable()->after('model');
            $table->string('engine_size')->nullable()->after('fuel_type');
            $table->decimal('fuel_consumption', 4, 2)->nullable()->after('engine_size');
            $table->boolean('has_insurance')->default(false)->after('features');
            $table->boolean('instant_booking')->default(false)->after('has_insurance');
            $table->integer('min_rental_days')->default(1)->after('instant_booking');
            $table->integer('max_rental_days')->nullable()->after('min_rental_days');
            $table->string('pickup_location')->nullable()->after('postal_code');
            $table->json('availability_schedule')->nullable()->after('is_available');
            
            $table->index(['city', 'status', 'is_available']);
            $table->index(['brand', 'model']);
            $table->index(['daily_rate']);
            $table->index(['year']);
            $table->index(['fuel_type', 'transmission']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['city', 'status', 'is_available']);
            $table->dropIndex(['brand', 'model']);
            $table->dropIndex(['daily_rate']);
            $table->dropIndex(['year']);
            $table->dropIndex(['fuel_type', 'transmission']);
            
            $table->dropColumn([
                'vehicle_type',
                'engine_size',
                'fuel_consumption',
                'has_insurance',
                'instant_booking',
                'min_rental_days',
                'max_rental_days',
                'pickup_location',
                'availability_schedule'
            ]);
        });
    }
};
