<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class NotificationPreferences extends Model
{
    protected $fillable = [
        'user_id',
        'nearby_rentals',
        'pickup_reminders',
        'area_alerts',
        'promotional',
        'new_listings',
        'price_drops',
        'share_location',
        'notification_radius',
        'favorite_locations',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'active_days',
        'push_enabled',
        'email_enabled',
        'sms_enabled',
        'frequency',
        'max_per_day',
    ];

    protected $casts = [
        'nearby_rentals' => 'boolean',
        'pickup_reminders' => 'boolean',
        'area_alerts' => 'boolean',
        'promotional' => 'boolean',
        'new_listings' => 'boolean',
        'price_drops' => 'boolean',
        'share_location' => 'boolean',
        'favorite_locations' => 'array',
        'quiet_hours_enabled' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
        'active_days' => 'array',
        'push_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
    ];

    protected $attributes = [
        'nearby_rentals' => true,
        'pickup_reminders' => true,
        'area_alerts' => true,
        'promotional' => false,
        'new_listings' => true,
        'price_drops' => true,
        'share_location' => false,
        'notification_radius' => 10000,
        'quiet_hours_enabled' => false,
        'push_enabled' => true,
        'email_enabled' => false,
        'sms_enabled' => false,
        'frequency' => 'realtime',
        'max_per_day' => 10,
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function shouldReceiveNotification(string $type): bool
    {
        return match ($type) {
            'nearby_rental' => $this->nearby_rentals,
            'pickup_reminder' => $this->pickup_reminders,
            'area_alert' => $this->area_alerts,
            'promotional' => $this->promotional,
            'new_listing' => $this->new_listings,
            'price_drop' => $this->price_drops,
            default => true,
        };
    }

    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled || !$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now();
        $startTime = Carbon::createFromFormat('H:i', $this->quiet_hours_start->format('H:i'));
        $endTime = Carbon::createFromFormat('H:i', $this->quiet_hours_end->format('H:i'));

        // Handle overnight quiet hours (e.g., 22:00 to 08:00)
        if ($startTime->isAfter($endTime)) {
            return $now->format('H:i') >= $startTime->format('H:i') || 
                   $now->format('H:i') <= $endTime->format('H:i');
        }

        // Regular quiet hours (e.g., 12:00 to 14:00)
        return $now->format('H:i') >= $startTime->format('H:i') && 
               $now->format('H:i') <= $endTime->format('H:i');
    }

    public function isDayActive(): bool
    {
        if (!$this->active_days || empty($this->active_days)) {
            return true; // All days active by default
        }

        $currentDay = strtolower(now()->format('l'));
        return in_array($currentDay, array_map('strtolower', $this->active_days));
    }

    public function canReceivePushNotification(): bool
    {
        return $this->push_enabled && 
               $this->isDayActive() && 
               !$this->isInQuietHours();
    }

    public function canReceiveEmailNotification(): bool
    {
        return $this->email_enabled && $this->isDayActive();
    }

    public function canReceiveSmsNotification(): bool
    {
        return $this->sms_enabled && 
               $this->isDayActive() && 
               !$this->isInQuietHours();
    }

    public function hasReachedDailyLimit(): bool
    {
        $today = now()->startOfDay();
        $count = $this->user->geoNotifications()
                          ->where('sent_at', '>=', $today)
                          ->count();

        return $count >= $this->max_per_day;
    }

    public function shouldThrottleNotification(): bool
    {
        if ($this->frequency === 'realtime') {
            return false;
        }

        $lastNotification = $this->user->geoNotifications()
                                     ->where('sent_at', '>', now()->subHour())
                                     ->latest('sent_at')
                                     ->first();

        if (!$lastNotification) {
            return false;
        }

        return match ($this->frequency) {
            'hourly' => $lastNotification->sent_at->isAfter(now()->subHour()),
            'daily' => $lastNotification->sent_at->isAfter(now()->subDay()),
            'weekly' => $lastNotification->sent_at->isAfter(now()->subWeek()),
            default => false,
        };
    }

    public function addFavoriteLocation(array $location): void
    {
        $favorites = $this->favorite_locations ?? [];
        
        // Check if location already exists
        $exists = collect($favorites)->contains(function ($fav) use ($location) {
            return abs($fav['latitude'] - $location['latitude']) < 0.001 &&
                   abs($fav['longitude'] - $location['longitude']) < 0.001;
        });

        if (!$exists) {
            $favorites[] = array_merge($location, [
                'added_at' => now()->toISOString(),
                'id' => uniqid(),
            ]);

            $this->update(['favorite_locations' => $favorites]);
        }
    }

    public function removeFavoriteLocation(string $locationId): void
    {
        $favorites = collect($this->favorite_locations ?? [])
                        ->reject(fn ($location) => $location['id'] === $locationId)
                        ->values()
                        ->toArray();

        $this->update(['favorite_locations' => $favorites]);
    }

    public function getDistance(): int
    {
        return $this->notification_radius;
    }

    public static function getDefaultPreferences(): array
    {
        return [
            'nearby_rentals' => true,
            'pickup_reminders' => true,
            'area_alerts' => true,
            'promotional' => false,
            'new_listings' => true,
            'price_drops' => true,
            'share_location' => false,
            'notification_radius' => 10000,
            'quiet_hours_enabled' => false,
            'push_enabled' => true,
            'email_enabled' => false,
            'sms_enabled' => false,
            'frequency' => 'realtime',
            'max_per_day' => 10,
            'active_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
        ];
    }
}