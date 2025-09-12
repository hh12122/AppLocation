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
        Schema::create('geo_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'nearby_rental', 'pickup_reminder', 'area_alert', etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional payload
            
            // Location data
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('radius')->default(5000); // Radius in meters
            $table->string('location_name')->nullable();
            
            // Targeting
            $table->json('target_criteria')->nullable(); // User criteria for targeting
            $table->boolean('is_active')->default(true);
            
            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->string('status')->default('pending'); // pending, sent, read, clicked, failed
            
            // Schedule
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['latitude', 'longitude']);
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_notifications');
    }
};