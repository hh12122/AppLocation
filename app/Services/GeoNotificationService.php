<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\GeoNotification;
use App\Models\UserLocation;
use App\Models\NotificationPreferences;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GeoLocationNotification;

class GeoNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Send notifications to users based on their location
     */
    public function sendLocationBasedNotifications(): void
    {
        Log::info('Starting geolocation notification process');

        $notifications = GeoNotification::active()
            ->notExpired()
            ->where(function ($query) {
                $query->where('status', 'pending')
                      ->orWhere(function ($q) {
                          $q->where('status', 'scheduled')
                            ->where('scheduled_for', '<=', now());
                      });
            })
            ->get();

        Log::info("Found {$notifications->count()} notifications to process");

        foreach ($notifications as $notification) {
            $this->processNotification($notification);
        }
    }

    /**
     * Process a single notification
     */
    public function processNotification(GeoNotification $notification): void
    {
        try {
            $eligibleUsers = $this->getEligibleUsers($notification);
            
            Log::info("Processing notification {$notification->id}, found {$eligibleUsers->count()} eligible users");

            foreach ($eligibleUsers as $user) {
                $this->sendToUser($notification, $user);
            }

            $notification->markAsSent();
        } catch (\Exception $e) {
            Log::error("Failed to process notification {$notification->id}: " . $e->getMessage());
            $notification->markAsFailed();
        }
    }

    /**
     * Get users eligible for a specific notification
     */
    public function getEligibleUsers(GeoNotification $notification): Collection
    {
        return User::with(['currentLocation', 'notificationPreferences'])
            ->whereHas('currentLocation', function ($query) use ($notification) {
                $query->selectRaw(
                    "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) * 1000 as distance",
                    [$notification->latitude, $notification->longitude, $notification->latitude]
                )->havingRaw('distance <= ?', [$notification->radius]);
            })
            ->whereHas('notificationPreferences', function ($query) use ($notification) {
                $query->where('push_enabled', true)
                      ->where('share_location', true);
            })
            ->get()
            ->filter(fn (User $user) => $notification->shouldSendToUser($user));
    }

    /**
     * Send notification to a specific user
     */
    public function sendToUser(GeoNotification $notification, User $user): void
    {
        try {
            // Send push notification
            if ($user->notificationPreferences->canReceivePushNotification()) {
                $this->sendPushNotification($notification, $user);
            }

            // Send email if enabled
            if ($user->notificationPreferences->canReceiveEmailNotification()) {
                $this->sendEmailNotification($notification, $user);
            }

            // Send SMS if enabled
            if ($user->notificationPreferences->canReceiveSmsNotification()) {
                $this->sendSmsNotification($notification, $user);
            }

            Log::info("Sent notification {$notification->id} to user {$user->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send notification {$notification->id} to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Send push notification
     */
    private function sendPushNotification(GeoNotification $notification, User $user): void
    {
        // Use the existing notification system
        $this->notificationService->sendNotification($user, [
            'type' => 'geo_notification',
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => array_merge($notification->data ?? [], [
                'notification_id' => $notification->id,
                'latitude' => $notification->latitude,
                'longitude' => $notification->longitude,
            ]),
        ]);
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(GeoNotification $notification, User $user): void
    {
        $user->notify(new GeoLocationNotification($notification));
    }

    /**
     * Send SMS notification
     */
    private function sendSmsNotification(GeoNotification $notification, User $user): void
    {
        // SMS implementation would go here
        // Could integrate with services like Twilio, AWS SNS, etc.
        Log::info("SMS notification would be sent to user {$user->id}");
    }

    /**
     * Create a new rental nearby notification
     */
    public function createNearbyRentalNotification(Vehicle $vehicle): GeoNotification
    {
        return GeoNotification::create([
            'type' => 'nearby_rental',
            'title' => 'New Vehicle Available Nearby',
            'message' => "A {$vehicle->brand} {$vehicle->model} is now available for rent near you!",
            'latitude' => $vehicle->latitude,
            'longitude' => $vehicle->longitude,
            'radius' => 5000, // 5km radius
            'location_name' => $vehicle->pickup_location ?? $vehicle->city,
            'data' => [
                'vehicle_id' => $vehicle->id,
                'vehicle_data' => [
                    'id' => $vehicle->id,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'price_per_day' => $vehicle->price_per_day,
                    'image' => $vehicle->images->first()?->image_path,
                ],
                'action_url' => route('vehicles.show', $vehicle),
            ],
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Create pickup reminder notification
     */
    public function createPickupReminder(User $user, $rental): GeoNotification
    {
        $vehicle = $rental->vehicle;

        return GeoNotification::create([
            'user_id' => $user->id,
            'type' => 'pickup_reminder',
            'title' => 'Pickup Reminder',
            'message' => "Don't forget to pick up your {$vehicle->brand} {$vehicle->model} today!",
            'latitude' => $vehicle->latitude,
            'longitude' => $vehicle->longitude,
            'radius' => 10000, // 10km radius
            'location_name' => $vehicle->pickup_location ?? $vehicle->city,
            'data' => [
                'rental_id' => $rental->id,
                'vehicle_id' => $vehicle->id,
                'pickup_time' => $rental->start_date,
                'action_url' => route('rentals.show', $rental),
            ],
            'scheduled_for' => $rental->start_date->subHours(2),
        ]);
    }

    /**
     * Create area alert notification
     */
    public function createAreaAlert(
        string $title,
        string $message,
        float $latitude,
        float $longitude,
        int $radius,
        array $targetCriteria = []
    ): GeoNotification {
        return GeoNotification::create([
            'type' => 'area_alert',
            'title' => $title,
            'message' => $message,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius,
            'target_criteria' => $targetCriteria,
            'expires_at' => now()->addDays(1),
        ]);
    }

    /**
     * Update user location and trigger relevant notifications
     */
    public function updateUserLocationAndNotify(User $user, array $locationData): UserLocation
    {
        $location = UserLocation::createFromBrowser($user, $locationData);
        
        // Find relevant notifications for this location
        $relevantNotifications = GeoNotification::forUserLocation($user)
            ->where('status', 'sent')
            ->where(function ($query) {
                $query->whereNull('sent_at')
                      ->orWhere('sent_at', '>', now()->subHours(24));
            })
            ->get();

        foreach ($relevantNotifications as $notification) {
            if ($notification->shouldSendToUser($user)) {
                $this->sendToUser($notification, $user);
            }
        }

        return $location;
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_notifications' => GeoNotification::count(),
            'active_notifications' => GeoNotification::active()->count(),
            'sent_today' => GeoNotification::where('sent_at', '>=', now()->startOfDay())->count(),
            'pending_notifications' => GeoNotification::pending()->count(),
            'users_with_location' => User::whereHas('currentLocation')->count(),
            'users_with_push_enabled' => User::whereHas('notificationPreferences', function ($query) {
                $query->where('push_enabled', true);
            })->count(),
            'notifications_by_type' => GeoNotification::groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    /**
     * Clean up old notifications
     */
    public function cleanup(): void
    {
        // Remove expired notifications older than 30 days
        GeoNotification::where('expires_at', '<', now()->subDays(30))
            ->delete();

        // Remove old user locations (keep only last 100 per user)
        $userIds = User::pluck('id');
        
        foreach ($userIds as $userId) {
            $oldLocations = UserLocation::where('user_id', $userId)
                ->where('is_current', false)
                ->orderBy('created_at', 'desc')
                ->skip(100)
                ->pluck('id');

            UserLocation::whereIn('id', $oldLocations)->delete();
        }

        Log::info('Cleaned up old geo-notifications and user locations');
    }
}