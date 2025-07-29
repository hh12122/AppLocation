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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('brand');
            $table->string('model');
            $table->integer('year');
            $table->string('color');
            $table->string('license_plate')->unique();
            $table->integer('mileage');
            $table->enum('fuel_type', ['gasoline', 'diesel', 'electric', 'hybrid']);
            $table->enum('transmission', ['manual', 'automatic']);
            $table->integer('seats');
            $table->integer('doors');
            $table->text('description')->nullable();
            $table->json('features')->nullable(); // climatisation, GPS, etc.
            $table->decimal('daily_rate', 8, 2);
            $table->decimal('weekly_rate', 8, 2)->nullable();
            $table->decimal('monthly_rate', 8, 2)->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('postal_code');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->boolean('is_available')->default(true);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->integer('rental_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
