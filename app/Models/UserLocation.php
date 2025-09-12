<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserLocation extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'source',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'ip_address',
        'user_agent',
        'is_current',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_current' => 'boolean',
    ];

    protected $attributes = [
        'source' => 'browser',
        'is_current' => true,
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeWithinRadius($query, $latitude, $longitude, $radius)
    {
        return $query->selectRaw(
            "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$latitude, $longitude, $latitude]
        )->havingRaw("distance <= ?", [$radius / 1000]);
    }

    // Accessors
    protected function formattedAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([
                    $this->address,
                    $this->city,
                    $this->state,
                    $this->country,
                ]);

                return implode(', ', $parts);
            }
        );
    }

    protected function coordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ]
        );
    }

    // Helper methods
    public function calculateDistanceTo($latitude, $longitude): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($latitude - $this->latitude);
        $lonDelta = deg2rad($longitude - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c * 1000; // Convert to meters
    }

    public function makeCurrentLocation(): void
    {
        // Set all other locations for this user as not current
        $this->user->locations()->update(['is_current' => false]);
        
        // Set this location as current
        $this->update(['is_current' => true]);
    }

    public function reverseGeocode(): array
    {
        // This would integrate with a geocoding service like Nominatim
        // For now, return empty array
        return [];
    }

    public function isNearby($latitude, $longitude, $radius = 1000): bool
    {
        $distance = $this->calculateDistanceTo($latitude, $longitude);
        return $distance <= $radius;
    }

    public function isAccurate($maxAccuracy = 100): bool
    {
        return $this->accuracy && $this->accuracy <= $maxAccuracy;
    }

    public function isRecent($hours = 1): bool
    {
        return $this->created_at->isAfter(now()->subHours($hours));
    }

    public function toMapData(): array
    {
        return [
            'id' => $this->id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'accuracy' => $this->accuracy,
            'address' => $this->formatted_address,
            'source' => $this->source,
            'is_current' => $this->is_current,
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    public static function createFromCoordinates(
        User $user,
        float $latitude,
        float $longitude,
        array $options = []
    ): self {
        $location = self::create(array_merge([
            'user_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'source' => 'manual',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $options));

        $location->makeCurrentLocation();

        return $location;
    }

    public static function createFromBrowser(
        User $user,
        array $locationData
    ): self {
        return self::createFromCoordinates(
            $user,
            $locationData['latitude'],
            $locationData['longitude'],
            [
                'accuracy' => $locationData['accuracy'] ?? null,
                'source' => 'browser',
                'address' => $locationData['address'] ?? null,
                'city' => $locationData['city'] ?? null,
                'state' => $locationData['state'] ?? null,
                'country' => $locationData['country'] ?? null,
                'postal_code' => $locationData['postal_code'] ?? null,
            ]
        );
    }
}