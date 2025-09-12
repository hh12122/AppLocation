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
        Schema::create('equipment_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade');
            
            // Booking periods - flexible for different rental units
            $table->timestamp('start_datetime'); // Can be date + time for hourly rentals
            $table->timestamp('end_datetime');
            $table->integer('duration_value'); // 2 hours, 5 days, 1 week, etc.
            $table->enum('duration_unit', ['hour', 'day', 'week', 'month']);
            
            // Pricing at booking time
            $table->decimal('unit_rate', 8, 2); // Rate per hour/day/week/month
            $table->decimal('subtotal', 10, 2);
            $table->decimal('security_deposit', 8, 2)->default(0);
            $table->decimal('cleaning_fee', 8, 2)->default(0);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('service_fee', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('owner_payout', 10, 2);
            
            // Delivery and pickup
            $table->enum('fulfillment_type', ['pickup', 'delivery', 'both'])->default('pickup');
            $table->string('delivery_address')->nullable();
            $table->string('pickup_address')->nullable();
            $table->timestamp('delivery_time')->nullable();
            $table->timestamp('pickup_time')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->text('pickup_instructions')->nullable();
            
            // Booking status
            $table->enum('status', [
                'pending',          // En attente d'approbation
                'confirmed',        // Confirmée
                'preparing',        // En préparation (nettoyage, maintenance)
                'ready',           // Prête pour récupération/livraison
                'delivered',        // Livrée/récupérée
                'in_use',          // En cours d'utilisation
                'returned',         // Rendue
                'completed',        // Terminée et vérifiée
                'cancelled_renter', // Annulée par le locataire
                'cancelled_owner',  // Annulée par le propriétaire
                'cancelled_admin',  // Annulée par l'admin
                'damaged',          // Endommagée
                'lost',            // Perdue
                'dispute'           // Litige
            ])->default('pending');
            
            // Payment status
            $table->enum('payment_status', [
                'pending',
                'authorized',
                'paid',
                'deposit_held',
                'deposit_released',
                'partially_refunded',
                'refunded',
                'failed'
            ])->default('pending');
            
            // Renter information and requirements
            $table->text('usage_purpose')->nullable(); // Why they need it
            $table->json('renter_details')->nullable(); // Experience level, certifications, etc.
            $table->text('special_requests')->nullable();
            $table->json('license_verification')->nullable(); // License details if required
            
            // Actual delivery/pickup/return times
            $table->timestamp('actual_delivery')->nullable();
            $table->timestamp('actual_pickup')->nullable();
            $table->timestamp('actual_return')->nullable();
            $table->json('delivery_confirmation')->nullable(); // Photos, signatures, etc.
            $table->json('return_confirmation')->nullable();
            
            // Condition tracking
            $table->json('pre_rental_condition')->nullable(); // Condition check before rental
            $table->json('post_rental_condition')->nullable(); // Condition check after return
            $table->text('damage_report')->nullable();
            $table->decimal('damage_cost', 8, 2)->default(0);
            $table->boolean('damage_covered_by_deposit')->default(true);
            
            // Communication and notes
            $table->text('owner_notes')->nullable();
            $table->text('renter_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('owner_contacted')->default(false);
            $table->timestamp('last_contact_at')->nullable();
            
            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->json('cancellation_policy_applied')->nullable();
            
            // Extensions and modifications
            $table->timestamp('extension_requested_until')->nullable();
            $table->enum('extension_status', ['none', 'requested', 'approved', 'denied'])->default('none');
            $table->decimal('extension_cost', 8, 2)->default(0);
            
            // Reviews and ratings
            $table->boolean('renter_reviewed')->default(false);
            $table->boolean('owner_reviewed')->default(false);
            $table->timestamp('review_deadline')->nullable();
            
            // Important timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Expiration for pending bookings
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['equipment_id', 'status']);
            $table->index(['renter_id', 'status']);
            $table->index(['start_datetime', 'end_datetime']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_status']);
            $table->index(['fulfillment_type']);
            
            // Prevent double bookings
            $table->unique(['equipment_id', 'start_datetime', 'status'], 'unique_equipment_datetime_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_bookings');
    }
};