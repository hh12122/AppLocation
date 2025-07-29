<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'owner_id',
        'brand',
        'model',
        'year',
        'color',
        'license_plate',
        'mileage',
        'fuel_type',
        'transmission',
        'seats',
        'doors',
        'description',
        'features',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'address',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'status',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'daily_rate' => 'decimal:2',
            'weekly_rate' => 'decimal:2',
            'monthly_rate' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_available' => 'boolean',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('type', 'vehicle');
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
}
