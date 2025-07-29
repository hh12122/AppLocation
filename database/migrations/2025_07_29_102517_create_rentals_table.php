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
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('daily_rate', 8, 2);
            $table->integer('total_days');
            $table->decimal('deposit', 8, 2)->nullable();
            $table->text('special_requests')->nullable();
            $table->datetime('pickup_datetime')->nullable();
            $table->datetime('return_datetime')->nullable();
            $table->integer('pickup_mileage')->nullable();
            $table->integer('return_mileage')->nullable();
            $table->text('pickup_notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->json('pickup_images')->nullable();
            $table->json('return_images')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
