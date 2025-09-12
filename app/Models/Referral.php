<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'referral_code',
        'status',
        'conversion_type',
        'converted_at',
        'reward_amount',
        'reward_paid',
        'reward_paid_at',
        'metadata',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
        'reward_paid_at' => 'datetime',
        'metadata' => 'array',
        'reward_paid' => 'boolean',
        'reward_amount' => 'decimal:2',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    public function markAsCompleted(string $conversionType = 'registration'): void
    {
        $this->update([
            'status' => 'completed',
            'conversion_type' => $conversionType,
            'converted_at' => now(),
        ]);

        // Award the referral reward
        $this->awardReferralReward($conversionType);
    }

    public function awardReferralReward(string $conversionType): void
    {
        $rewardAmount = $this->getRewardAmount($conversionType);
        
        if ($rewardAmount > 0) {
            // Create reward for referrer
            ReferralReward::create([
                'user_id' => $this->referrer_id,
                'referral_id' => $this->id,
                'reward_type' => 'credit',
                'amount' => $rewardAmount,
                'status' => 'awarded',
                'title' => $this->getRewardTitle($conversionType),
                'description' => $this->getRewardDescription($conversionType),
                'expires_at' => now()->addYear(), // Credits expire after 1 year
            ]);

            // Update referrer's credits and count
            $this->referrer->increment('referral_credits', $rewardAmount);
            $this->referrer->increment('referral_count');

            // Also give welcome bonus to referred user
            $this->awardWelcomeBonus();

            $this->update([
                'reward_amount' => $rewardAmount,
                'reward_paid' => true,
                'reward_paid_at' => now(),
            ]);
        }
    }

    private function awardWelcomeBonus(): void
    {
        $bonusAmount = 10.00; // Welcome bonus for new users

        ReferralReward::create([
            'user_id' => $this->referred_user_id,
            'referral_id' => $this->id,
            'reward_type' => 'bonus',
            'amount' => $bonusAmount,
            'status' => 'awarded',
            'title' => 'Bonus de bienvenue',
            'description' => 'Crédit de bienvenue pour votre inscription via un parrainage',
            'expires_at' => now()->addYear(),
        ]);

        $this->referredUser->increment('referral_credits', $bonusAmount);
    }

    private function getRewardAmount(string $conversionType): float
    {
        return match ($conversionType) {
            'registration' => 20.00,
            'first_rental' => 50.00,
            'first_listing' => 30.00,
            default => 20.00,
        };
    }

    private function getRewardTitle(string $conversionType): string
    {
        return match ($conversionType) {
            'registration' => 'Parrainage réussi - Inscription',
            'first_rental' => 'Parrainage réussi - Première location',
            'first_listing' => 'Parrainage réussi - Première annonce',
            default => 'Récompense de parrainage',
        };
    }

    private function getRewardDescription(string $conversionType): string
    {
        $referredUserName = $this->referredUser->name;
        
        return match ($conversionType) {
            'registration' => "Félicitations ! {$referredUserName} s'est inscrit grâce à votre code de parrainage.",
            'first_rental' => "Excellent ! {$referredUserName} a effectué sa première location grâce à votre parrainage.",
            'first_listing' => "Parfait ! {$referredUserName} a publié sa première annonce grâce à votre parrainage.",
            default => "Récompense pour le parrainage de {$referredUserName}",
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForReferrer($query, User $user)
    {
        return $query->where('referrer_id', $user->id);
    }

    public function scopeForReferredUser($query, User $user)
    {
        return $query->where('referred_user_id', $user->id);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }
}