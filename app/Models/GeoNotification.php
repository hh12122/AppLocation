<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class GeoNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'latitude',
        'longitude',
        'radius',
        'location_name',
        'target_criteria',
        'is_active',
        'sent_at',
        'read_at',
        'clicked_at',
        'status',
        'scheduled_for',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'target_criteria' => 'array',
        'is_active' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_active' => true,
        'radius' => 5000,
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_for')
                    ->where('scheduled_for', '<=', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeWithinRadius($query, $latitude, $longitude, $radius = null)
    {
        $radius = $radius ?? 50000; // Default 50km
        
        return $query->selectRaw(
            "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$latitude, $longitude, $latitude]
        )->havingRaw("distance <= ?", [$radius / 1000]);
    }

    public function scopeForUserLocation($query, User $user)
    {
        $location = $user->currentLocation;
        
        if (!$location) {
            return $query->whereRaw('0 = 1'); // No results if no location
        }

        return $query->active()
                    ->notExpired()
                    ->where(function ($q) use ($location) {
                        $q->whereRaw(
                            "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) * 1000 <= radius",
                            [$location->latitude, $location->longitude, $location->latitude]
                        );
                    });
    }

    // Accessors & Mutators
    protected function isRead(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->read_at)
        );
    }

    protected function isClicked(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->clicked_at)
        );
    }

    protected function isSent(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'sent' || !is_null($this->sent_at)
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at && $this->expires_at->isPast()
        );
    }

    // Helper methods
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    public function markAsClicked(): void
    {
        $this->update([
            'status' => 'clicked',
            'clicked_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }

    public function getDistanceFromUser(User $user): ?float
    {
        $userLocation = $user->currentLocation;
        
        if (!$userLocation) {
            return null;
        }

        return $this->calculateDistance(
            $userLocation->latitude,
            $userLocation->longitude,
            $this->latitude,
            $this->longitude
        );
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c * 1000; // Convert to meters
    }

    public function shouldSendToUser(User $user): bool
    {
        // Check if notification is active and not expired
        if (!$this->is_active || $this->is_expired) {
            return false;
        }

        // Check if user has appropriate preferences
        $preferences = $user->notificationPreferences;
        if (!$preferences || !$preferences->shouldReceiveNotification($this->type)) {
            return false;
        }

        // Check if user is within radius
        $distance = $this->getDistanceFromUser($user);
        if ($distance === null || $distance > $this->radius) {
            return false;
        }

        // Check quiet hours
        if ($preferences->isInQuietHours()) {
            return false;
        }

        // Check target criteria
        if ($this->target_criteria && !$this->matchesTargetCriteria($user)) {
            return false;
        }

        return true;
    }

    private function matchesTargetCriteria(User $user): bool
    {
        if (!$this->target_criteria) {
            return true;
        }

        // Example criteria matching logic
        $criteria = $this->target_criteria;

        // Age range
        if (isset($criteria['age_min']) || isset($criteria['age_max'])) {
            $age = $user->age;
            if (isset($criteria['age_min']) && $age < $criteria['age_min']) {
                return false;
            }
            if (isset($criteria['age_max']) && $age > $criteria['age_max']) {
                return false;
            }
        }

        // User type (renter, owner)
        if (isset($criteria['user_types']) && is_array($criteria['user_types'])) {
            $userTypes = [];
            if ($user->vehicles()->count() > 0) {
                $userTypes[] = 'owner';
            }
            if ($user->rentals()->count() > 0) {
                $userTypes[] = 'renter';
            }

            if (!array_intersect($userTypes, $criteria['user_types'])) {
                return false;
            }
        }

        return true;
    }
}