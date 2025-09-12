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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Notification types
            $table->boolean('nearby_rentals')->default(true);
            $table->boolean('pickup_reminders')->default(true);
            $table->boolean('area_alerts')->default(true);
            $table->boolean('promotional')->default(false);
            $table->boolean('new_listings')->default(true);
            $table->boolean('price_drops')->default(true);
            
            // Location settings
            $table->boolean('share_location')->default(false);
            $table->integer('notification_radius')->default(10000); // in meters
            $table->json('favorite_locations')->nullable(); // Array of saved locations
            
            // Timing preferences
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->json('active_days')->nullable(); // Days of week to receive notifications
            
            // Delivery preferences
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            
            // Frequency settings
            $table->string('frequency')->default('realtime'); // realtime, hourly, daily, weekly
            $table->integer('max_per_day')->default(10);
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};