<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'bio',
        'avatar',
        'address',
        'city',
        'postal_code',
        'country',
        'date_of_birth',
        'driving_license_number',
        'driving_license_expiry',
        'driving_license_front',
        'driving_license_back',
        'driving_license_status',
        'driving_license_verified_at',
        'driving_license_rejection_reason',
        'is_owner',
        'is_admin',
        'is_verified',
        'rating',
        'rating_count',
        'referral_code',
        'referred_by',
        'referral_credits',
        'referral_count',
        'referral_code_generated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'driving_license_expiry' => 'date',
            'driving_license_verified_at' => 'datetime',
            'is_owner' => 'boolean',
            'is_admin' => 'boolean',
            'is_verified' => 'boolean',
            'rating' => 'decimal:2',
            'referral_credits' => 'decimal:2',
            'referral_code_generated_at' => 'datetime',
        ];
    }

    // Relations
    public function ownedVehicles()
    {
        return $this->hasMany(Vehicle::class, 'owner_id');
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class, 'renter_id');
    }

    public function givenReviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteVehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'favorites')
            ->withPivot('notes', 'created_at')
            ->withTimestamps();
    }

    public function conversationsAsRenter()
    {
        return $this->hasMany(Conversation::class, 'renter_id');
    }

    public function conversationsAsOwner()
    {
        return $this->hasMany(Conversation::class, 'owner_id');
    }

    public function conversations()
    {
        return Conversation::where('renter_id', $this->id)
            ->orWhere('owner_id', $this->id);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Referral relations
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function receivedReferrals()
    {
        return $this->hasMany(Referral::class, 'referred_user_id');
    }

    public function referralRewards()
    {
        return $this->hasMany(ReferralReward::class);
    }

    // Helpers
    public function getAverageRating()
    {
        return $this->rating;
    }

    public function canRent(): bool
    {
        return $this->driving_license_expiry && 
               $this->driving_license_expiry > now() &&
               $this->driving_license_status !== 'rejected';
    }

    public function hasValidLicense(): bool
    {
        return $this->driving_license_status === 'verified' &&
               $this->driving_license_expiry &&
               $this->driving_license_expiry > now();
    }

    public function licenseNeedsVerification(): bool
    {
        return !$this->driving_license_number ||
               !$this->driving_license_expiry ||
               $this->driving_license_status === 'pending' ||
               ($this->driving_license_expiry && $this->driving_license_expiry < now());
    }

    public function canOwn(): bool
    {
        return $this->is_verified && $this->is_owner;
    }

    public function hasFavorited(Vehicle $vehicle): bool
    {
        return $this->favorites()->where('vehicle_id', $vehicle->id)->exists();
    }

    // Referral methods
    public function generateReferralCode(): string
    {
        if (!$this->referral_code) {
            do {
                $code = 'CAR' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
            } while (static::where('referral_code', $code)->exists());

            $this->update([
                'referral_code' => $code,
                'referral_code_generated_at' => now()
            ]);
        }

        return $this->referral_code;
    }

    public function getReferralUrl(): string
    {
        $code = $this->generateReferralCode();
        return route('register') . '?ref=' . $code;
    }

    public function hasReferralCode(): bool
    {
        return !empty($this->referral_code);
    }

    public function wasReferred(): bool
    {
        return !empty($this->referred_by);
    }

    public function getAvailableCredits(): float
    {
        return (float) $this->referralRewards()
            ->available()
            ->sum('amount');
    }

    public function getTotalEarnedCredits(): float
    {
        return (float) $this->referralRewards()
            ->where('reward_type', 'credit')
            ->whereIn('status', ['awarded', 'used'])
            ->sum('amount');
    }

    public function getSuccessfulReferralsCount(): int
    {
        return $this->referrals()->completed()->count();
    }

    public function canUseReferralCredits(float $amount): bool
    {
        return $this->getAvailableCredits() >= $amount;
    }

    public function useReferralCredits(float $amount, Rental $rental = null): bool
    {
        if (!$this->canUseReferralCredits($amount)) {
            return false;
        }

        $remainingAmount = $amount;
        $rewards = $this->referralRewards()
            ->available()
            ->where('reward_type', 'credit')
            ->orderBy('expires_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($rewards as $reward) {
            if ($remainingAmount <= 0) break;

            $usedAmount = min($reward->amount, $remainingAmount);
            
            if ($usedAmount == $reward->amount) {
                // Use the entire reward
                $reward->markAsUsed($rental);
            } else {
                // Partially use the reward (split it)
                $reward->update(['amount' => $reward->amount - $usedAmount]);
                
                // Create a new "used" reward for the used portion
                ReferralReward::create([
                    'user_id' => $this->id,
                    'referral_id' => $reward->referral_id,
                    'reward_type' => 'credit',
                    'amount' => $usedAmount,
                    'status' => 'used',
                    'title' => $reward->title,
                    'description' => 'Utilisation partielle: ' . $reward->description,
                    'used_at' => now(),
                    'used_in_rental_id' => $rental?->id,
                ]);
            }

            $remainingAmount -= $usedAmount;
        }

        // Update user's available credits
        $this->decrement('referral_credits', $amount);

        return true;
    }

    public function getReferralStats(): array
    {
        return [
            'total_referrals' => $this->referrals()->count(),
            'successful_referrals' => $this->getSuccessfulReferralsCount(),
            'pending_referrals' => $this->referrals()->pending()->count(),
            'total_earned' => $this->getTotalEarnedCredits(),
            'available_credits' => $this->getAvailableCredits(),
            'referral_rate' => $this->referrals()->count() > 0 
                ? ($this->getSuccessfulReferralsCount() / $this->referrals()->count()) * 100 
                : 0,
        ];
    }
}
