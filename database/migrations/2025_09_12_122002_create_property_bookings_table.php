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
        Schema::create('property_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('guest_id')->constrained('users')->onDelete('cascade');
            
            // Booking dates
            $table->date('checkin_date');
            $table->date('checkout_date');
            $table->integer('nights_count');
            $table->integer('guests_count');
            $table->integer('adults_count')->default(1);
            $table->integer('children_count')->default(0);
            $table->integer('infants_count')->default(0);
            
            // Pricing breakdown
            $table->decimal('nightly_rate', 8, 2); // Tarif par nuit au moment de la réservation
            $table->decimal('subtotal', 10, 2); // Sous-total (nuits × tarif)
            $table->decimal('cleaning_fee', 8, 2)->default(0);
            $table->decimal('service_fee', 8, 2)->default(0); // Frais de service de la plateforme
            $table->decimal('tax_amount', 8, 2)->default(0); // Taxes
            $table->decimal('total_amount', 10, 2); // Montant total
            $table->decimal('security_deposit', 8, 2)->default(0);
            $table->decimal('host_payout', 10, 2); // Montant versé à l'hôte
            
            // Booking status
            $table->enum('status', [
                'pending',          // En attente d'approbation de l'hôte
                'confirmed',        // Confirmée
                'checked_in',       // Arrivée effectuée
                'checked_out',      // Départ effectué
                'completed',        // Terminée
                'cancelled_guest',  // Annulée par le voyageur
                'cancelled_host',   // Annulée par l'hôte
                'cancelled_admin',  // Annulée par l'admin
                'no_show',          // Client non présenté
                'dispute'           // Litige
            ])->default('pending');
            
            // Payment information
            $table->enum('payment_status', [
                'pending',
                'authorized',
                'paid',
                'partially_refunded',
                'refunded',
                'failed'
            ])->default('pending');
            
            // Guest information
            $table->text('special_requests')->nullable();
            $table->json('guest_details')->nullable(); // Informations supplémentaires sur les voyageurs
            $table->text('purpose_of_trip')->nullable(); // Raison du voyage
            
            // Check-in/Check-out details
            $table->timestamp('actual_checkin')->nullable();
            $table->timestamp('actual_checkout')->nullable();
            $table->json('checkin_details')->nullable(); // Détails de l'arrivée
            $table->json('checkout_details')->nullable(); // Détails du départ
            
            // Communication
            $table->text('host_notes')->nullable(); // Notes de l'hôte
            $table->text('admin_notes')->nullable(); // Notes administratives
            $table->boolean('host_contacted')->default(false);
            $table->timestamp('last_contact_at')->nullable();
            
            // Cancellation information
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->json('cancellation_policy_applied')->nullable();
            
            // Reviews and ratings
            $table->boolean('guest_reviewed')->default(false);
            $table->boolean('host_reviewed')->default(false);
            $table->timestamp('review_deadline')->nullable(); // Date limite pour laisser un avis
            
            // Timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Date d'expiration si en attente
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['property_id', 'status']);
            $table->index(['guest_id', 'status']);
            $table->index(['checkin_date', 'checkout_date']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_status']);
            
            // Unique constraint to prevent double bookings
            $table->unique(['property_id', 'checkin_date', 'status'], 'unique_property_date_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_bookings');
    }
};