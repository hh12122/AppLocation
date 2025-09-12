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
        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Location data
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('accuracy')->nullable(); // GPS accuracy in meters
            $table->string('source')->default('browser'); // browser, mobile, manual
            
            // Address information (from geocoding)
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            
            // Metadata
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('is_current')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['latitude', 'longitude']);
            $table->index(['user_id', 'is_current']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
};