<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'user_id',
        'payment_method',
        'status',
        'amount',
        'platform_fee',
        'gateway_fee',
        'owner_payout',
        'refunded_amount',
        'referral_credits_used',
        'final_amount',
        'currency',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_refund_id',
        'paypal_order_id',
        'paypal_capture_id',
        'paypal_refund_id',
        'payment_details',
        'metadata',
        'paid_at',
        'refunded_at',
        'failed_at',
        'failure_reason',
        'notes'
    ];

    protected $casts = [
        'payment_details' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the rental associated with the payment.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Get the user who made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted amount (convert from cents to euros).
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get formatted platform fee.
     */
    public function getFormattedPlatformFeeAttribute(): string
    {
        return number_format($this->platform_fee / 100, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get formatted owner payout.
     */
    public function getFormattedOwnerPayoutAttribute(): string
    {
        return number_format($this->owner_payout / 100, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get formatted referral credits used.
     */
    public function getFormattedReferralCreditsUsedAttribute(): string
    {
        return number_format($this->referral_credits_used / 100, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get formatted final amount.
     */
    public function getFormattedFinalAmountAttribute(): string
    {
        return number_format($this->final_amount / 100, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Check if payment used referral credits.
     */
    public function usedReferralCredits(): bool
    {
        return $this->referral_credits_used > 0;
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is refundable.
     */
    public function isRefundable(): bool
    {
        return $this->status === 'completed' && 
               $this->refunded_amount < $this->amount;
    }

    /**
     * Check if payment is fully refunded.
     */
    public function isFullyRefunded(): bool
    {
        return $this->status === 'refunded' && 
               $this->refunded_amount >= $this->amount;
    }

    /**
     * Calculate platform fee based on amount.
     */
    public static function calculatePlatformFee(int $amount): int
    {
        $percentage = config('payment.fees.platform_percentage', 10);
        return (int) round($amount * ($percentage / 100));
    }

    /**
     * Calculate gateway fee based on payment method and amount.
     */
    public static function calculateGatewayFee(string $method, int $amount): int
    {
        if ($method === 'stripe') {
            $percentage = config('payment.fees.stripe_fee_percentage', 2.9);
            $fixed = config('payment.fees.stripe_fee_fixed', 0.30) * 100; // Convert to cents
            return (int) round(($amount * ($percentage / 100)) + $fixed);
        }
        
        if ($method === 'paypal') {
            $percentage = config('payment.fees.paypal_fee_percentage', 3.4);
            $fixed = config('payment.fees.paypal_fee_fixed', 0.35) * 100; // Convert to cents
            return (int) round(($amount * ($percentage / 100)) + $fixed);
        }
        
        return 0; // No fee for cash payments
    }

    /**
     * Calculate owner payout after fees.
     */
    public static function calculateOwnerPayout(int $amount, int $platformFee, int $gatewayFee): int
    {
        return $amount - $platformFee - $gatewayFee;
    }

    /**
     * Scope to get payments by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get payments by method.
     */
    public function scopeMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope to get completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}