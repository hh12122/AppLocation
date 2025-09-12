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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            
            // Basic information
            $table->string('name');
            $table->text('description');
            $table->enum('category', [
                'sports_equipment',  // Équipements de sport
                'tools_material',    // Outils et matériel
                'boats',            // Bateaux
                'spaces'            // Espaces
            ]);
            $table->string('subcategory'); // ski, bike, drill, boat, meeting_room, etc.
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->integer('year')->nullable();
            $table->enum('condition', ['new', 'excellent', 'good', 'fair', 'poor'])->default('good');
            
            // Physical attributes
            $table->decimal('length', 8, 2)->nullable(); // For boats, skis, etc.
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('size')->nullable(); // S, M, L, XL for clothing/equipment
            $table->integer('capacity')->nullable(); // Person capacity for boats/spaces
            $table->decimal('area_sqm', 8, 2)->nullable(); // For spaces
            
            // Category-specific JSON attributes
            $table->json('sports_attributes')->nullable(); // Sport type, skill level, season, etc.
            $table->json('tools_attributes')->nullable(); // Power requirements, accessories, safety, etc.
            $table->json('boat_attributes')->nullable(); // Engine power, fuel, license required, marina, etc.
            $table->json('space_attributes')->nullable(); // Amenities, capacity, business facilities, etc.
            
            // Features and equipment
            $table->json('features')->nullable(); // General features list
            $table->json('included_items')->nullable(); // What's included with the rental
            $table->json('safety_equipment')->nullable(); // Safety items included
            $table->text('usage_instructions')->nullable();
            $table->text('safety_instructions')->nullable();
            
            // Location
            $table->string('address');
            $table->string('city');
            $table->string('postal_code');
            $table->string('country')->default('France');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('pickup_instructions')->nullable();
            $table->boolean('delivery_available')->default(false);
            $table->decimal('delivery_radius', 5, 2)->nullable(); // km
            $table->decimal('delivery_fee', 8, 2)->nullable();
            
            // Pricing (flexible for different rental types)
            $table->decimal('hourly_rate', 8, 2)->nullable(); // For spaces, some equipment
            $table->decimal('daily_rate', 8, 2)->nullable(); // Most common
            $table->decimal('weekly_rate', 8, 2)->nullable();
            $table->decimal('monthly_rate', 8, 2)->nullable();
            $table->decimal('security_deposit', 8, 2)->default(0);
            $table->decimal('cleaning_fee', 8, 2)->default(0);
            $table->integer('min_rental_duration')->default(1); // in hours or days
            $table->integer('max_rental_duration')->nullable();
            $table->enum('rental_unit', ['hour', 'day', 'week', 'month'])->default('day');
            
            // Availability and booking
            $table->enum('status', ['active', 'inactive', 'maintenance', 'rented', 'unavailable'])->default('active');
            $table->boolean('is_available')->default(true);
            $table->boolean('instant_booking')->default(false);
            $table->json('availability_calendar')->nullable();
            $table->integer('preparation_time')->default(0); // Time needed between rentals
            $table->enum('pickup_type', [
                'pickup_only',      // Must pick up
                'delivery_only',    // Only delivered
                'both'              // Can choose
            ])->default('pickup_only');
            
            // Requirements and restrictions
            $table->integer('min_age')->nullable();
            $table->boolean('license_required')->default(false);
            $table->string('license_type')->nullable(); // Driving, boating, etc.
            $table->boolean('experience_required')->default(false);
            $table->text('rental_requirements')->nullable();
            $table->json('restrictions')->nullable(); // Usage restrictions
            
            // Insurance and liability
            $table->boolean('insurance_included')->default(false);
            $table->text('insurance_details')->nullable();
            $table->text('liability_terms')->nullable();
            
            // Statistics and ratings
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->integer('rental_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamp('last_rented_at')->nullable();
            
            // Featured and promotional
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamp('discount_expires_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['category', 'status', 'is_available']);
            $table->index(['subcategory', 'city']);
            $table->index(['daily_rate', 'hourly_rate']);
            $table->index(['rating', 'rental_count']);
            $table->index(['is_featured', 'is_premium']);
            $table->index(['latitude', 'longitude']);
            $table->index(['instant_booking']);
            $table->index(['delivery_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};