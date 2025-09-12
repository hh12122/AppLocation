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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            
            // Basic property information
            $table->string('title');
            $table->text('description');
            $table->enum('property_type', [
                'apartment', 
                'house', 
                'studio', 
                'villa', 
                'loft', 
                'townhouse',
                'cottage',
                'chalet',
                'castle',
                'other'
            ]);
            $table->enum('room_type', [
                'entire_place',     // Logement entier
                'private_room',     // Chambre privée
                'shared_room'       // Chambre partagée
            ])->default('entire_place');
            
            // Property specifications
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->integer('beds');
            $table->integer('max_guests');
            $table->decimal('area_sqm', 8, 2)->nullable(); // Surface en m²
            $table->integer('floor_level')->nullable(); // Étage
            $table->boolean('has_elevator')->default(false);
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_terrace')->default(false);
            $table->boolean('has_garden')->default(false);
            
            // Amenities (JSON for flexibility)
            $table->json('amenities')->nullable(); // WiFi, kitchen, washing machine, etc.
            $table->json('safety_features')->nullable(); // Smoke detector, first aid, etc.
            $table->json('house_rules')->nullable(); // No smoking, pets allowed, etc.
            
            // Location
            $table->string('address');
            $table->string('city');
            $table->string('postal_code');
            $table->string('country')->default('France');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('location_description')->nullable(); // Description du quartier
            
            // Pricing
            $table->decimal('nightly_rate', 8, 2); // Prix par nuit
            $table->decimal('weekly_rate', 8, 2)->nullable(); // Prix à la semaine
            $table->decimal('monthly_rate', 8, 2)->nullable(); // Prix au mois
            $table->decimal('cleaning_fee', 8, 2)->default(0); // Frais de ménage
            $table->decimal('security_deposit', 8, 2)->default(0); // Caution
            $table->integer('min_nights')->default(1); // Minimum de nuits
            $table->integer('max_nights')->nullable(); // Maximum de nuits
            
            // Availability and booking
            $table->enum('status', ['active', 'inactive', 'maintenance', 'suspended'])->default('active');
            $table->boolean('is_available')->default(true);
            $table->boolean('instant_booking')->default(false);
            $table->json('availability_calendar')->nullable(); // Calendrier de disponibilité
            $table->integer('preparation_time')->default(0); // Temps de préparation entre les locations (en heures)
            
            // Check-in/Check-out
            $table->time('checkin_start')->default('15:00');
            $table->time('checkin_end')->default('22:00');
            $table->time('checkout_time')->default('11:00');
            $table->enum('checkin_method', [
                'self_checkin',     // Arrivée autonome
                'host_greeting',    // Accueil par l'hôte
                'concierge',        // Concierge
                'lockbox',          // Boîte à clés
                'smart_lock'        // Serrure connectée
            ])->default('host_greeting');
            $table->text('checkin_instructions')->nullable();
            
            // Statistics and ratings
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->integer('booking_count')->default(0);
            $table->integer('view_count')->default(0);
            
            // Host information
            $table->boolean('host_verified')->default(false);
            $table->text('host_about')->nullable();
            $table->json('host_languages')->nullable(); // Langues parlées par l'hôte
            $table->integer('host_response_time')->nullable(); // Temps de réponse en minutes
            $table->decimal('host_response_rate', 5, 2)->nullable(); // Taux de réponse en %
            
            // Additional features for premium listings
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_luxury')->default(false);
            $table->boolean('is_eco_friendly')->default(false);
            $table->boolean('is_business_ready')->default(false); // Adapté aux voyageurs d'affaires
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['city', 'status', 'is_available']);
            $table->index(['property_type', 'room_type']);
            $table->index(['nightly_rate']);
            $table->index(['max_guests', 'bedrooms']);
            $table->index(['rating', 'rating_count']);
            $table->index(['instant_booking']);
            $table->index(['is_featured', 'is_luxury']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};