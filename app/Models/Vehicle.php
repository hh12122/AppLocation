<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vehicle extends Model
{
    protected $fillable = [
        'owner_id',
        'brand',
        'model',
        'vehicle_type',
        'year',
        'color',
        'license_plate',
        'mileage',
        'fuel_type',
        'engine_size',
        'fuel_consumption',
        'transmission',
        'seats',
        'doors',
        'description',
        'features',
        'has_insurance',
        'instant_booking',
        'min_rental_days',
        'max_rental_days',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'address',
        'city',
        'postal_code',
        'pickup_location',
        'latitude',
        'longitude',
        'status',
        'is_available',
        'availability_schedule',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'availability_schedule' => 'array',
            'daily_rate' => 'decimal:2',
            'weekly_rate' => 'decimal:2',
            'monthly_rate' => 'decimal:2',
            'fuel_consumption' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_available' => 'boolean',
            'has_insurance' => 'boolean',
            'instant_booking' => 'boolean',
            'rating' => 'decimal:2',
        ];
    }

    // Relations
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
    //public function reviews(): HasMany
    //{
       // return $this->hasMany(Review::class)->where('type', 'vehicle');
    //}

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    // Keep backwards compatibility
    public function legacyFavorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites', 'favoritable_id', 'user_id')
            ->where('favorites.favoritable_type', self::class)
            ->withPivot('notes', 'created_at')
            ->withTimestamps();
    }

    // Helpers
    public function getPrimaryImage()
    {
        return $this->images()->where('is_primary', true)->first()
            ?? $this->images()->orderBy('sort_order')->first();
    }

    public function getFullName(): string
    {
        return "{$this->year} {$this->brand} {$this->model}";
    }

    public function isAvailableForPeriod($startDate, $endDate): bool
    {
        if (!$this->is_available || $this->status !== 'active') {
            return false;
        }

        return !$this->rentals()
            ->whereIn('status', ['confirmed', 'active'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();
    }

    public function calculateTotalPrice($startDate, $endDate): float
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $days = $start->diff($end)->days + 1;

        if ($days >= 30 && $this->monthly_rate) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            return ($months * $this->monthly_rate) + ($remainingDays * $this->daily_rate);
        }

        if ($days >= 7 && $this->weekly_rate) {
            $weeks = floor($days / 7);
            $remainingDays = $days % 7;
            return ($weeks * $this->weekly_rate) + ($remainingDays * $this->daily_rate);
        }

        return $days * $this->daily_rate;
    }

    // Query Scopes for performance optimization
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_available', true);
    }

    public function scopeWithBasicRelations($query)
    {
        return $query->with([
            'owner:id,name,rating',
            'images' => function ($q) {
                $q->select('id', 'vehicle_id', 'image_path', 'is_primary', 'sort_order')
                  ->where('is_primary', true)
                  ->orWhere(function($query) {
                      $query->orderBy('sort_order')->limit(1);
                  });
            }
        ]);
    }

    public function scopeWithReviewStats($query)
    {
        return $query->withCount('reviews')->withAvg('reviews', 'rating');
    }

    public function scopeAvailableForDates($query, $startDate, $endDate)
    {
        return $query->whereDoesntHave('rentals', function ($q) use ($startDate, $endDate) {
            $q->whereIn('status', ['confirmed', 'active'])
              ->where(function ($subQuery) use ($startDate, $endDate) {
                  $subQuery->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($nestedQuery) use ($startDate, $endDate) {
                          $nestedQuery->where('start_date', '<=', $startDate)
                                     ->where('end_date', '>=', $endDate);
                      });
              });
        });
    }

    public function scopeNearLocation($query, $lat, $lng, $radius)
    {
        return $query->selectRaw("*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
    }
}
