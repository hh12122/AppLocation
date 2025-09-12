<?php

namespace App\Listeners;

use App\Events\VehicleCreated;
use App\Services\GeoNotificationService;
use Illuminate\Support\Facades\Log;

class SendNearbyVehicleNotification
{
    public function __construct(
        private readonly GeoNotificationService $geoNotificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(VehicleCreated $event): void
    {
        try {
            $vehicle = $event->vehicle;
            
            // Only send notifications if vehicle has location data
            if ($vehicle->latitude && $vehicle->longitude) {
                $notification = $this->geoNotificationService
                    ->createNearbyRentalNotification($vehicle);
                
                Log::info("Created nearby rental notification for vehicle {$vehicle->id}");
                
                // Process the notification immediately for nearby users
                $this->geoNotificationService->processNotification($notification);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send nearby vehicle notification: " . $e->getMessage());
        }
    }
}