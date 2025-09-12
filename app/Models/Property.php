<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'property_type',
        'room_type',
        'bedrooms',
        'bathrooms',
        'beds',
        'max_guests',
        'area_sqm',
        'floor_level',
        'has_elevator',
        'has_parking',
        'has_balcony',
        'has_terrace',
        'has_garden',
        'amenities',
        'safety_features',
        'house_rules',
        'address',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'location_description',
        'nightly_rate',
        'weekly_rate',
        'monthly_rate',
        'cleaning_fee',
        'security_deposit',
        'min_nights',
        'max_nights',
        'status',
        'is_available',
        'instant_booking',
        'availability_calendar',
        'preparation_time',
        'checkin_start',
        'checkin_end',
        'checkout_time',
        'checkin_method',
        'checkin_instructions',
        'rating',
        'rating_count',
        'booking_count',
        'view_count',
        'host_verified',
        'host_about',
        'host_languages',
        'host_response_time',
        'host_response_rate',
        'is_featured',
        'is_luxury',
        'is_eco_friendly',
        'is_business_ready',
    ];

    protected $casts = [
        'amenities' => 'array',
        'safety_features' => 'array',
        'house_rules' => 'array',
        'availability_calendar' => 'array',
        'host_languages' => 'array',
        'has_elevator' => 'boolean',
        'has_parking' => 'boolean',
        'has_balcony' => 'boolean',
        'has_terrace' => 'boolean',
        'has_garden' => 'boolean',
        'is_available' => 'boolean',
        'instant_booking' => 'boolean',
        'host_verified' => 'boolean',
        'is_featured' => 'boolean',
        'is_luxury' => 'boolean',
        'is_eco_friendly' => 'boolean',
        'is_business_ready' => 'boolean',
        'checkin_start' => 'datetime:H:i',
        'checkin_end' => 'datetime:H:i',
        'checkout_time' => 'datetime:H:i',
        'nightly_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'rating' => 'decimal:2',
        'host_response_rate' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'area_sqm' => 'decimal:2',
    ];

    /**
     * Get the owner of the property.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the property images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    /**
     * Get the primary image.
     */
    public function primaryImage()
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

    /**
     * Get all bookings for the property.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(PropertyBooking::class);
    }

    /**
     * Get active bookings.
     */
    public function activeBookings(): HasMany
    {
        return $this->hasMany(PropertyBooking::class)
            ->whereIn('status', ['confirmed', 'checked_in']);
    }

    /**
     * Get reviews for the property.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewable_id')
            ->where('reviewable_type', self::class);
    }

    /**
     * Get favorites relationship.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'property_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Check if property is available for given dates.
     */
    public function isAvailableForDates(Carbon $checkinDate, Carbon $checkoutDate): bool
    {
        if (!$this->is_available || $this->status !== 'active') {
            return false;
        }

        // Check for overlapping bookings
        $hasOverlappingBooking = $this->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where(function ($query) use ($checkinDate, $checkoutDate) {
                $query->where(function ($q) use ($checkinDate, $checkoutDate) {
                    // Booking starts before checkout and ends after checkin
                    $q->where('checkin_date', '<', $checkoutDate)
                      ->where('checkout_date', '>', $checkinDate);
                });
            })
            ->exists();

        return !$hasOverlappingBooking;
    }

    /**
     * Calculate total price for given dates and guests.
     */
    public function calculateTotalPrice(Carbon $checkinDate, Carbon $checkoutDate, int $guestsCount = 1): array
    {
        $nights = $checkinDate->diffInDays($checkoutDate);
        
        // Base price calculation
        $subtotal = $this->nightly_rate * $nights;
        
        // Apply weekly/monthly discounts if applicable
        if ($nights >= 28 && $this->monthly_rate) {
            $months = floor($nights / 28);
            $remainingNights = $nights % 28;
            $subtotal = ($this->monthly_rate * $months) + ($this->nightly_rate * $remainingNights);
        } elseif ($nights >= 7 && $this->weekly_rate) {
            $weeks = floor($nights / 7);
            $remainingNights = $nights % 7;
            $subtotal = ($this->weekly_rate * $weeks) + ($this->nightly_rate * $remainingNights);
        }

        // Additional fees
        $cleaningFee = $this->cleaning_fee ?? 0;
        $serviceFee = $subtotal * 0.12; // 12% service fee
        $taxAmount = ($subtotal + $serviceFee) * 0.20; // 20% VAT in France
        
        $totalAmount = $subtotal + $cleaningFee + $serviceFee + $taxAmount;

        return [
            'nights' => $nights,
            'nightly_rate' => $this->nightly_rate,
            'subtotal' => round($subtotal, 2),
            'cleaning_fee' => round($cleaningFee, 2),
            'service_fee' => round($serviceFee, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'security_deposit' => $this->security_deposit ?? 0,
        ];
    }

    /**
     * Get property type label.
     */
    public function getPropertyTypeLabel(): string
    {
        $labels = [
            'apartment' => 'Appartement',
            'house' => 'Maison',
            'studio' => 'Studio',
            'villa' => 'Villa',
            'loft' => 'Loft',
            'townhouse' => 'Maison de ville',
            'cottage' => 'Cottage',
            'chalet' => 'Chalet',
            'castle' => 'Château',
            'other' => 'Autre',
        ];

        return $labels[$this->property_type] ?? $this->property_type;
    }

    /**
     * Get room type label.
     */
    public function getRoomTypeLabel(): string
    {
        $labels = [
            'entire_place' => 'Logement entier',
            'private_room' => 'Chambre privée',
            'shared_room' => 'Chambre partagée',
        ];

        return $labels[$this->room_type] ?? $this->room_type;
    }

    /**
     * Get checkin method label.
     */
    public function getCheckinMethodLabel(): string
    {
        $labels = [
            'self_checkin' => 'Arrivée autonome',
            'host_greeting' => 'Accueil par l\'hôte',
            'concierge' => 'Concierge',
            'lockbox' => 'Boîte à clés',
            'smart_lock' => 'Serrure connectée',
        ];

        return $labels[$this->checkin_method] ?? $this->checkin_method;
    }

    /**
     * Update property rating.
     */
    public function updateRating(): void
    {
        $avgRating = $this->reviews()->avg('overall_rating') ?? 0;
        $ratingCount = $this->reviews()->count();

        $this->update([
            'rating' => round($avgRating, 2),
            'rating_count' => $ratingCount,
        ]);
    }

    /**
     * Increment view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Scope for active properties.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_available', true);
    }

    /**
     * Scope for featured properties.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for luxury properties.
     */
    public function scopeLuxury(Builder $query): Builder
    {
        return $query->where('is_luxury', true);
    }

    /**
     * Scope for instant booking properties.
     */
    public function scopeInstantBooking(Builder $query): Builder
    {
        return $query->where('instant_booking', true);
    }

    /**
     * Scope for properties in a specific city.
     */
    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope for properties by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('property_type', $type);
    }

    /**
     * Scope for properties by room type.
     */
    public function scopeRoomType(Builder $query, string $roomType): Builder
    {
        return $query->where('room_type', $roomType);
    }

    /**
     * Scope for properties with minimum guests capacity.
     */
    public function scopeMinGuests(Builder $query, int $guests): Builder
    {
        return $query->where('max_guests', '>=', $guests);
    }

    /**
     * Scope for properties within price range.
     */
    public function scopePriceRange(Builder $query, float $minPrice = null, float $maxPrice = null): Builder
    {
        if ($minPrice !== null) {
            $query->where('nightly_rate', '>=', $minPrice);
        }
        
        if ($maxPrice !== null) {
            $query->where('nightly_rate', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Scope for properties with specific amenities.
     */
    public function scopeWithAmenities(Builder $query, array $amenities): Builder
    {
        foreach ($amenities as $amenity) {
            $query->whereJsonContains('amenities', $amenity);
        }

        return $query;
    }

    /**
     * Scope for properties within radius of coordinates.
     */
    public function scopeWithinRadius(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->whereRaw("
            (? * acos(
                cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )) <= ?
        ", [$earthRadius, $latitude, $longitude, $latitude, $radiusKm]);
    }
}