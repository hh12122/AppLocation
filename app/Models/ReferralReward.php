<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referral_id',
        'reward_type',
        'amount',
        'currency',
        'status',
        'title',
        'description',
        'expires_at',
        'used_at',
        'used_in_rental_id',
        'conditions',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'conditions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function usedInRental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'used_in_rental_id');
    }

    public function markAsUsed(Rental $rental = null): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'used_in_rental_id' => $rental?->id,
        ]);

        // Deduct from user's available credits if it's a credit reward
        if ($this->reward_type === 'credit') {
            $this->user->decrement('referral_credits', $this->amount);
        }
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);

        // Remove expired credits from user's balance
        if ($this->reward_type === 'credit' && $this->status === 'awarded') {
            $this->user->decrement('referral_credits', $this->amount);
        }
    }

    public function canBeUsed(): bool
    {
        return $this->status === 'awarded' && 
               ($this->expires_at === null || $this->expires_at > now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at < now();
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAwarded(): bool
    {
        return $this->status === 'awarded';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . $this->currency;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'awarded' => 'Accordé',
            'used' => 'Utilisé',
            'expired' => 'Expiré',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'awarded' => 'bg-green-100 text-green-800',
            'used' => 'bg-blue-100 text-blue-800',
            'expired' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getRewardTypeIconAttribute(): string
    {
        return match ($this->reward_type) {
            'credit' => '💳',
            'discount' => '🎫',
            'bonus' => '🎁',
            'milestone' => '🏆',
            default => '🎉',
        };
    }

    // Scopes
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeAwarded($query)
    {
        return $query->where('status', 'awarded');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'awarded')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('status', 'awarded')
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public static function expireOldRewards(): int
    {
        $expiredRewards = static::where('status', 'awarded')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredRewards as $reward) {
            $reward->markAsExpired();
            $count++;
        }

        return $count;
    }
}