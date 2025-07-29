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
        'is_owner',
        'is_verified',
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
            'is_owner' => 'boolean',
            'is_verified' => 'boolean',
            'rating' => 'decimal:2',
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

    // Helpers
    public function getAverageRating()
    {
        return $this->rating;
    }

    public function canRent(): bool
    {
        return $this->is_verified && $this->driving_license_expiry > now();
    }

    public function canOwn(): bool
    {
        return $this->is_verified && $this->is_owner;
    }
}
