<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EquipmentBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'renter_id',
        'start_datetime',
        'end_datetime',
        'duration_value',
        'duration_unit',
        'unit_rate',
        'subtotal',
        'security_deposit',
        'cleaning_fee',
        'delivery_fee',
        'service_fee',
        'total_amount',
        'owner_payout',
        'fulfillment_type',
        'delivery_address',
        'pickup_address',
        'delivery_time',
        'pickup_time',
        'delivery_instructions',
        'pickup_instructions',
        'status',
        'payment_status',
        'usage_purpose',
        'renter_details',
        'special_requests',
        'license_verification',
        'actual_delivery',
        'actual_pickup',
        'actual_return',
        'delivery_confirmation',
        'return_confirmation',
        'pre_rental_condition',
        'post_rental_condition',
        'damage_report',
        'damage_cost',
        'damage_covered_by_deposit',
        'owner_notes',
        'renter_notes',
        'admin_notes',
        'owner_contacted',
        'last_contact_at',
        'cancelled_at',
        'cancellation_reason',
        'refund_amount',
        'cancellation_policy_applied',
        'extension_requested_until',
        'extension_status',
        'extension_cost',
        'renter_reviewed',
        'owner_reviewed',
        'review_deadline',
        'confirmed_at',
        'expires_at',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'delivery_time' => 'datetime',
        'pickup_time' => 'datetime',
        'actual_delivery' => 'datetime',
        'actual_pickup' => 'datetime',
        'actual_return' => 'datetime',
        'renter_details' => 'array',
        'license_verification' => 'array',
        'delivery_confirmation' => 'array',
        'return_confirmation' => 'array',
        'pre_rental_condition' => 'array',
        'post_rental_condition' => 'array',
        'cancellation_policy_applied' => 'array',
        'unit_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'owner_payout' => 'decimal:2',
        'damage_cost' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'extension_cost' => 'decimal:2',
        'owner_contacted' => 'boolean',
        'damage_covered_by_deposit' => 'boolean',
        'renter_reviewed' => 'boolean',
        'owner_reviewed' => 'boolean',
        'last_contact_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'extension_requested_until' => 'datetime',
        'review_deadline' => 'datetime',
        'confirmed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the equipment for this booking.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Get the renter who made the booking.
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    /**
     * Get payments for this booking.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'rental_id'); // Reusing existing payment system
    }

    /**
     * Get reviews for this booking.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'booking_id');
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'preparing' => 'En préparation',
            'ready' => 'Prête',
            'delivered' => 'Livrée/Récupérée',
            'in_use' => 'En cours',
            'returned' => 'Rendue',
            'completed' => 'Terminée',
            'cancelled_renter' => 'Annulée par le locataire',
            'cancelled_owner' => 'Annulée par le propriétaire',
            'cancelled_admin' => 'Annulée par l\'admin',
            'damaged' => 'Endommagée',
            'lost' => 'Perdue',
            'dispute' => 'Litige',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get payment status label.
     */
    public function getPaymentStatusLabel(): string
    {
        $labels = [
            'pending' => 'En attente',
            'authorized' => 'Autorisé',
            'paid' => 'Payé',
            'deposit_held' => 'Caution bloquée',
            'deposit_released' => 'Caution libérée',
            'partially_refunded' => 'Partiellement remboursé',
            'refunded' => 'Remboursé',
            'failed' => 'Échec',
        ];

        return $labels[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Get fulfillment type label.
     */
    public function getFulfillmentTypeLabel(): string
    {
        $labels = [
            'pickup' => 'Récupération',
            'delivery' => 'Livraison',
            'both' => 'Récupération et livraison',
        ];

        return $labels[$this->fulfillment_type] ?? $this->fulfillment_type;
    }

    /**
     * Get duration label.
     */
    public function getDurationLabel(): string
    {
        $unitLabels = [
            'hour' => 'heure',
            'day' => 'jour',
            'week' => 'semaine',
            'month' => 'mois',
        ];

        $unit = $unitLabels[$this->duration_unit] ?? $this->duration_unit;
        $plural = $this->duration_value > 1 ? 's' : '';
        
        return "{$this->duration_value} {$unit}{$plural}";
    }

    /**
     * Check if booking can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'preparing']) && 
               $this->start_datetime > now()->addHours(2); // 2-hour cancellation window
    }

    /**
     * Check if booking can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === 'pending' && 
               $this->expires_at > now();
    }

    /**
     * Check if pickup/delivery can be marked as done.
     */
    public function canBeDelivered(): bool
    {
        return $this->status === 'ready' && 
               $this->delivery_time <= now()->addHours(2) &&
               $this->payment_status === 'paid';
    }

    /**
     * Check if return can be processed.
     */
    public function canBeReturned(): bool
    {
        return in_array($this->status, ['delivered', 'in_use']);
    }

    /**
     * Check if review can be left.
     */
    public function canBeReviewed(): bool
    {
        return $this->status === 'completed' && 
               $this->review_deadline > now() &&
               !$this->renter_reviewed;
    }

    /**
     * Check if extension can be requested.
     */
    public function canRequestExtension(): bool
    {
        return in_array($this->status, ['delivered', 'in_use']) &&
               $this->extension_status === 'none' &&
               $this->end_datetime > now();
    }

    /**
     * Confirm the booking.
     */
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'review_deadline' => $this->end_datetime->addDays(14), // 14 days to leave a review
        ]);

        // Increment rental count for equipment
        $this->equipment->increment('rental_count');
        $this->equipment->update(['last_rented_at' => now()]);
    }

    /**
     * Cancel the booking.
     */
    public function cancel(string $reason, string $cancelledBy = 'renter'): void
    {
        $statusMap = [
            'renter' => 'cancelled_renter',
            'owner' => 'cancelled_owner',
            'admin' => 'cancelled_admin',
        ];

        $this->update([
            'status' => $statusMap[$cancelledBy] ?? 'cancelled_renter',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Mark as ready for pickup/delivery.
     */
    public function markAsReady(): void
    {
        $this->update([
            'status' => 'ready',
        ]);
    }

    /**
     * Mark as delivered/picked up.
     */
    public function markAsDelivered(array $confirmationData = []): void
    {
        $this->update([
            'status' => 'delivered',
            'actual_delivery' => now(),
            'delivery_confirmation' => $confirmationData,
        ]);

        // If it's a short rental (< 1 day), immediately mark as in use
        if ($this->duration_value < 1 && $this->duration_unit === 'hour') {
            $this->update(['status' => 'in_use']);
        }
    }

    /**
     * Mark as returned.
     */
    public function markAsReturned(array $conditionData = []): void
    {
        $this->update([
            'status' => 'returned',
            'actual_return' => now(),
            'return_confirmation' => $conditionData,
            'post_rental_condition' => $conditionData,
        ]);

        // Auto-complete if no damage reported
        if (empty($conditionData['damage_report'])) {
            $this->complete();
        }
    }

    /**
     * Complete the booking.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'payment_status' => 'deposit_released', // Release security deposit
        ]);
    }

    /**
     * Request extension.
     */
    public function requestExtension(Carbon $newEndTime, float $additionalCost): void
    {
        $this->update([
            'extension_requested_until' => $newEndTime,
            'extension_status' => 'requested',
            'extension_cost' => $additionalCost,
        ]);
    }

    /**
     * Approve extension.
     */
    public function approveExtension(): void
    {
        $this->update([
            'end_datetime' => $this->extension_requested_until,
            'extension_status' => 'approved',
            'total_amount' => $this->total_amount + $this->extension_cost,
        ]);
    }

    /**
     * Calculate refund amount based on cancellation policy.
     */
    public function calculateRefund(): float
    {
        $hoursUntilStart = now()->diffInHours($this->start_datetime, false);
        
        // Basic cancellation policy based on time until rental
        if ($hoursUntilStart >= 24) {
            return $this->total_amount; // Full refund
        } elseif ($hoursUntilStart >= 2) {
            return $this->total_amount * 0.5; // 50% refund
        } else {
            return 0; // No refund
        }
    }

    /**
     * Get booking reference number.
     */
    public function getReferenceAttribute(): string
    {
        return 'EB' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    // Scopes

    /**
     * Scope for active bookings.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['confirmed', 'preparing', 'ready', 'delivered', 'in_use']);
    }

    /**
     * Scope for pending bookings.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed bookings.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled bookings.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->whereIn('status', ['cancelled_renter', 'cancelled_owner', 'cancelled_admin']);
    }

    /**
     * Scope for upcoming bookings.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_datetime', '>', now())
                    ->whereIn('status', ['confirmed', 'preparing']);
    }

    /**
     * Scope for current bookings.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('start_datetime', '<=', now())
                    ->where('end_datetime', '>=', now())
                    ->whereIn('status', ['delivered', 'in_use']);
    }

    /**
     * Scope for bookings by renter.
     */
    public function scopeByRenter(Builder $query, int $renterId): Builder
    {
        return $query->where('renter_id', $renterId);
    }

    /**
     * Scope for bookings by equipment owner.
     */
    public function scopeByOwner(Builder $query, int $ownerId): Builder
    {
        return $query->whereHas('equipment', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });
    }

    /**
     * Scope for bookings by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->whereHas('equipment', function ($q) use ($category) {
            $q->where('category', $category);
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Set expiration time for pending bookings
        static::creating(function (EquipmentBooking $booking) {
            if ($booking->status === 'pending') {
                $booking->expires_at = now()->addHours(24); // 24 hours to confirm
            }
        });
    }
}