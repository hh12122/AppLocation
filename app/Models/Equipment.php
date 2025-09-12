<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'category',
        'subcategory',
        'brand',
        'model',
        'year',
        'condition',
        'length',
        'width',
        'height',
        'weight',
        'size',
        'capacity',
        'area_sqm',
        'sports_attributes',
        'tools_attributes',
        'boat_attributes',
        'space_attributes',
        'features',
        'included_items',
        'safety_equipment',
        'usage_instructions',
        'safety_instructions',
        'address',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'pickup_instructions',
        'delivery_available',
        'delivery_radius',
        'delivery_fee',
        'hourly_rate',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'security_deposit',
        'cleaning_fee',
        'min_rental_duration',
        'max_rental_duration',
        'rental_unit',
        'status',
        'is_available',
        'instant_booking',
        'availability_calendar',
        'preparation_time',
        'pickup_type',
        'min_age',
        'license_required',
        'license_type',
        'experience_required',
        'rental_requirements',
        'restrictions',
        'insurance_included',
        'insurance_details',
        'liability_terms',
        'rating',
        'rating_count',
        'rental_count',
        'view_count',
        'last_rented_at',
        'is_featured',
        'is_premium',
        'discount_percentage',
        'discount_expires_at',
    ];

    protected $casts = [
        'sports_attributes' => 'array',
        'tools_attributes' => 'array',
        'boat_attributes' => 'array',
        'space_attributes' => 'array',
        'features' => 'array',
        'included_items' => 'array',
        'safety_equipment' => 'array',
        'availability_calendar' => 'array',
        'restrictions' => 'array',
        'delivery_available' => 'boolean',
        'is_available' => 'boolean',
        'instant_booking' => 'boolean',
        'license_required' => 'boolean',
        'experience_required' => 'boolean',
        'insurance_included' => 'boolean',
        'is_featured' => 'boolean',
        'is_premium' => 'boolean',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'area_sqm' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'delivery_radius' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'rating' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'last_rented_at' => 'datetime',
        'discount_expires_at' => 'datetime',
    ];

    /**
     * Category configuration with subcategories and specific attributes.
     */
    public static function getCategoryConfig(): array
    {
        return [
            'sports_equipment' => [
                'label' => 'Équipements de sport',
                'subcategories' => [
                    'ski' => 'Ski & Snowboard',
                    'bike' => 'Vélos',
                    'camping' => 'Camping & Randonnée',
                    'water_sports' => 'Sports nautiques',
                    'fitness' => 'Fitness & Musculation',
                    'team_sports' => 'Sports collectifs',
                    'climbing' => 'Escalade & Alpinisme',
                    'golf' => 'Golf',
                    'tennis' => 'Tennis & Raquettes',
                    'other_sports' => 'Autres sports',
                ],
                'common_attributes' => ['sport_type', 'skill_level', 'season', 'size_guide'],
                'rental_units' => ['hour', 'day', 'week'],
            ],
            'tools_material' => [
                'label' => 'Outils & Matériel',
                'subcategories' => [
                    'power_tools' => 'Outils électriques',
                    'hand_tools' => 'Outils manuels',
                    'gardening' => 'Jardinage',
                    'construction' => 'Construction',
                    'automotive' => 'Automobile',
                    'electronics' => 'Électronique',
                    'event_equipment' => 'Matériel événementiel',
                    'cleaning' => 'Nettoyage',
                    'photography' => 'Photo & Vidéo',
                    'other_tools' => 'Autres outils',
                ],
                'common_attributes' => ['power_requirement', 'accessories_included', 'safety_requirements'],
                'rental_units' => ['hour', 'day', 'week'],
            ],
            'boats' => [
                'label' => 'Bateaux & Nautisme',
                'subcategories' => [
                    'motorboat' => 'Bateaux à moteur',
                    'sailboat' => 'Voiliers',
                    'jet_ski' => 'Jet-skis',
                    'inflatable' => 'Pneumatiques',
                    'kayak_canoe' => 'Kayaks & Canoës',
                    'fishing_boat' => 'Bateaux de pêche',
                    'luxury_yacht' => 'Yachts de luxe',
                    'catamaran' => 'Catamarans',
                    'other_boats' => 'Autres embarcations',
                ],
                'common_attributes' => ['engine_power', 'fuel_included', 'license_required', 'marina_location'],
                'rental_units' => ['hour', 'day', 'week'],
            ],
            'spaces' => [
                'label' => 'Espaces & Lieux',
                'subcategories' => [
                    'meeting_room' => 'Salles de réunion',
                    'event_space' => 'Espaces événementiels',
                    'storage' => 'Espaces de stockage',
                    'workshop' => 'Ateliers',
                    'studio' => 'Studios (photo/vidéo)',
                    'office' => 'Bureaux',
                    'coworking' => 'Espaces de coworking',
                    'parking' => 'Places de parking',
                    'warehouse' => 'Entrepôts',
                    'other_spaces' => 'Autres espaces',
                ],
                'common_attributes' => ['capacity', 'equipment_included', 'business_facilities'],
                'rental_units' => ['hour', 'day', 'week', 'month'],
            ],
        ];
    }

    /**
     * Get the owner of the equipment.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the equipment images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(EquipmentImage::class)->orderBy('sort_order');
    }

    /**
     * Get the primary image.
     */
    public function primaryImage()
    {
        return $this->hasOne(EquipmentImage::class)->where('is_primary', true);
    }

    /**
     * Get all bookings for the equipment.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(EquipmentBooking::class);
    }

    /**
     * Get active bookings.
     */
    public function activeBookings(): HasMany
    {
        return $this->hasMany(EquipmentBooking::class)
            ->whereIn('status', ['confirmed', 'preparing', 'ready', 'delivered', 'in_use']);
    }

    /**
     * Get reviews for the equipment.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewable_id')
            ->where('reviewable_type', self::class);
    }

    /**
     * Get favorites relationship.
     */
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * Get users who favorited this equipment.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'favoritable_id', 'user_id')
            ->where('favorites.favoritable_type', self::class)
            ->withTimestamps();
    }

    /**
     * Check if equipment is available for given dates/times.
     */
    public function isAvailableForPeriod(Carbon $startTime, Carbon $endTime): bool
    {
        if (!$this->is_available || $this->status !== 'active') {
            return false;
        }

        // Check for overlapping bookings
        $hasOverlappingBooking = $this->bookings()
            ->whereIn('status', ['confirmed', 'preparing', 'ready', 'delivered', 'in_use'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_datetime', '<', $endTime)
                      ->where('end_datetime', '>', $startTime);
            })
            ->exists();

        return !$hasOverlappingBooking;
    }

    /**
     * Calculate total price for given duration.
     */
    public function calculateTotalPrice(int $duration, string $unit = null): array
    {
        $unit = $unit ?: $this->rental_unit;
        $rate = $this->getRateForUnit($unit);
        
        if (!$rate) {
            throw new \InvalidArgumentException("No rate available for unit: {$unit}");
        }

        // Base price calculation
        $subtotal = $rate * $duration;
        
        // Apply discounts
        $discountAmount = 0;
        if ($this->discount_percentage > 0 && 
            (!$this->discount_expires_at || $this->discount_expires_at > now())) {
            $discountAmount = $subtotal * ($this->discount_percentage / 100);
            $subtotal -= $discountAmount;
        }

        // Additional fees
        $securityDeposit = $this->security_deposit ?? 0;
        $cleaningFee = $this->cleaning_fee ?? 0;
        $deliveryFee = $this->delivery_fee ?? 0;
        $serviceFee = $subtotal * 0.10; // 10% service fee
        
        $totalAmount = $subtotal + $cleaningFee + $deliveryFee + $serviceFee;

        return [
            'duration' => $duration,
            'unit' => $unit,
            'unit_rate' => $rate,
            'subtotal_before_discount' => $rate * $duration,
            'discount_amount' => $discountAmount,
            'subtotal' => round($subtotal, 2),
            'security_deposit' => round($securityDeposit, 2),
            'cleaning_fee' => round($cleaningFee, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'service_fee' => round($serviceFee, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Get rate for specific rental unit.
     */
    public function getRateForUnit(string $unit): ?float
    {
        return match($unit) {
            'hour' => $this->hourly_rate,
            'day' => $this->daily_rate,
            'week' => $this->weekly_rate,
            'month' => $this->monthly_rate,
            default => null,
        };
    }

    /**
     * Get category label.
     */
    public function getCategoryLabel(): string
    {
        return self::getCategoryConfig()[$this->category]['label'] ?? $this->category;
    }

    /**
     * Get subcategory label.
     */
    public function getSubcategoryLabel(): string
    {
        $config = self::getCategoryConfig();
        return $config[$this->category]['subcategories'][$this->subcategory] ?? $this->subcategory;
    }

    /**
     * Get condition label.
     */
    public function getConditionLabel(): string
    {
        $labels = [
            'new' => 'Neuf',
            'excellent' => 'Excellent',
            'good' => 'Bon',
            'fair' => 'Correct',
            'poor' => 'Mauvais',
        ];

        return $labels[$this->condition] ?? $this->condition;
    }

    /**
     * Get pickup type label.
     */
    public function getPickupTypeLabel(): string
    {
        $labels = [
            'pickup_only' => 'Récupération uniquement',
            'delivery_only' => 'Livraison uniquement',
            'both' => 'Récupération ou livraison',
        ];

        return $labels[$this->pickup_type] ?? $this->pickup_type;
    }

    /**
     * Get category-specific attributes.
     */
    public function getCategoryAttributes(): array
    {
        return match($this->category) {
            'sports_equipment' => $this->sports_attributes ?? [],
            'tools_material' => $this->tools_attributes ?? [],
            'boats' => $this->boat_attributes ?? [],
            'spaces' => $this->space_attributes ?? [],
            default => [],
        };
    }

    /**
     * Update equipment rating.
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

    // Scopes

    /**
     * Scope for active equipment.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_available', true);
    }

    /**
     * Scope for featured equipment.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for premium equipment.
     */
    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope for instant booking equipment.
     */
    public function scopeInstantBooking(Builder $query): Builder
    {
        return $query->where('instant_booking', true);
    }

    /**
     * Scope for equipment in a specific city.
     */
    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope for equipment by category.
     */
    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for equipment by subcategory.
     */
    public function scopeOfSubcategory(Builder $query, string $subcategory): Builder
    {
        return $query->where('subcategory', $subcategory);
    }

    /**
     * Scope for equipment within price range.
     */
    public function scopePriceRange(Builder $query, string $unit = 'day', float $minPrice = null, float $maxPrice = null): Builder
    {
        $column = $unit . '_rate';
        
        if ($minPrice !== null) {
            $query->where($column, '>=', $minPrice);
        }
        
        if ($maxPrice !== null) {
            $query->where($column, '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Scope for equipment with specific features.
     */
    public function scopeWithFeatures(Builder $query, array $features): Builder
    {
        foreach ($features as $feature) {
            $query->whereJsonContains('features', $feature);
        }

        return $query;
    }

    /**
     * Scope for equipment within radius of coordinates.
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

    /**
     * Scope for equipment available for delivery.
     */
    public function scopeWithDelivery(Builder $query): Builder
    {
        return $query->where('delivery_available', true);
    }

    /**
     * Scope for equipment that doesn't require license.
     */
    public function scopeNoLicenseRequired(Builder $query): Builder
    {
        return $query->where('license_required', false);
    }
}