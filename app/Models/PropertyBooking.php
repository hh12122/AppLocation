<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PropertyBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'guest_id',
        'checkin_date',
        'checkout_date',
        'nights_count',
        'guests_count',
        'adults_count',
        'children_count',
        'infants_count',
        'nightly_rate',
        'subtotal',
        'cleaning_fee',
        'service_fee',
        'tax_amount',
        'total_amount',
        'security_deposit',
        'host_payout',
        'status',
        'payment_status',
        'special_requests',
        'guest_details',
        'purpose_of_trip',
        'actual_checkin',
        'actual_checkout',
        'checkin_details',
        'checkout_details',
        'host_notes',
        'admin_notes',
        'host_contacted',
        'last_contact_at',
        'cancelled_at',
        'cancellation_reason',
        'refund_amount',
        'cancellation_policy_applied',
        'guest_reviewed',
        'host_reviewed',
        'review_deadline',
        'confirmed_at',
        'expires_at',
    ];

    protected $casts = [
        'checkin_date' => 'date',
        'checkout_date' => 'date',
        'guest_details' => 'array',
        'checkin_details' => 'array',
        'checkout_details' => 'array',
        'cancellation_policy_applied' => 'array',
        'subtotal' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'host_payout' => 'decimal:2',
        'nightly_rate' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'host_contacted' => 'boolean',
        'guest_reviewed' => 'boolean',
        'host_reviewed' => 'boolean',
        'actual_checkin' => 'datetime',
        'actual_checkout' => 'datetime',
        'last_contact_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'review_deadline' => 'datetime',
        'confirmed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the property for this booking.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the guest who made the booking.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_id');
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
            'checked_in' => 'Arrivée effectuée',
            'checked_out' => 'Départ effectué',
            'completed' => 'Terminée',
            'cancelled_guest' => 'Annulée par le voyageur',
            'cancelled_host' => 'Annulée par l\'hôte',
            'cancelled_admin' => 'Annulée par l\'admin',
            'no_show' => 'Client non présenté',
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
            'partially_refunded' => 'Partiellement remboursé',
            'refunded' => 'Remboursé',
            'failed' => 'Échec',
        ];

        return $labels[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Check if booking can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) && 
               $this->checkin_date > now()->addDays(1);
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
     * Check if check-in is possible.
     */
    public function canCheckIn(): bool
    {
        return $this->status === 'confirmed' && 
               $this->checkin_date <= now()->toDateString() &&
               $this->payment_status === 'paid';
    }

    /**
     * Check if check-out is possible.
     */
    public function canCheckOut(): bool
    {
        return $this->status === 'checked_in';
    }

    /**
     * Check if review can be left.
     */
    public function canBeReviewed(): bool
    {
        return $this->status === 'completed' && 
               $this->review_deadline > now() &&
               !$this->guest_reviewed;
    }

    /**
     * Confirm the booking.
     */
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'review_deadline' => $this->checkout_date->addDays(14), // 14 days to leave a review
        ]);

        // Increment booking count for property
        $this->property->increment('booking_count');
    }

    /**
     * Cancel the booking.
     */
    public function cancel(string $reason, string $cancelledBy = 'guest'): void
    {
        $statusMap = [
            'guest' => 'cancelled_guest',
            'host' => 'cancelled_host',
            'admin' => 'cancelled_admin',
        ];

        $this->update([
            'status' => $statusMap[$cancelledBy] ?? 'cancelled_guest',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Check-in the guest.
     */
    public function checkIn(array $details = []): void
    {
        $this->update([
            'status' => 'checked_in',
            'actual_checkin' => now(),
            'checkin_details' => $details,
        ]);
    }

    /**
     * Check-out the guest.
     */
    public function checkOut(array $details = []): void
    {
        $this->update([
            'status' => 'checked_out',
            'actual_checkout' => now(),
            'checkout_details' => $details,
        ]);

        // Auto-complete after checkout
        $this->complete();
    }

    /**
     * Complete the booking.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
        ]);
    }

    /**
     * Calculate refund amount based on cancellation policy.
     */
    public function calculateRefund(): float
    {
        $daysUntilCheckin = now()->diffInDays($this->checkin_date, false);
        
        // Basic cancellation policy
        if ($daysUntilCheckin >= 7) {
            return $this->total_amount; // Full refund
        } elseif ($daysUntilCheckin >= 1) {
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
        return 'PB' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for active bookings.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['confirmed', 'checked_in']);
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
        return $query->whereIn('status', ['cancelled_guest', 'cancelled_host', 'cancelled_admin']);
    }

    /**
     * Scope for upcoming bookings.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('checkin_date', '>', now()->toDateString())
                    ->whereIn('status', ['confirmed']);
    }

    /**
     * Scope for current bookings.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('checkin_date', '<=', now()->toDateString())
                    ->where('checkout_date', '>=', now()->toDateString())
                    ->whereIn('status', ['checked_in']);
    }

    /**
     * Scope for bookings by guest.
     */
    public function scopeByGuest(Builder $query, int $guestId): Builder
    {
        return $query->where('guest_id', $guestId);
    }

    /**
     * Scope for bookings by property owner.
     */
    public function scopeByOwner(Builder $query, int $ownerId): Builder
    {
        return $query->whereHas('property', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Set expiration time for pending bookings
        static::creating(function (PropertyBooking $booking) {
            if ($booking->status === 'pending') {
                $booking->expires_at = now()->addHours(24); // 24 hours to confirm
            }
        });
    }
}