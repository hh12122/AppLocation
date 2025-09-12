<?php

namespace App\Http\Controllers;

use App\Models\GeoNotification;
use App\Models\UserLocation;
use App\Services\GeoNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GeoNotificationController extends Controller
{
    public function __construct(
        private readonly GeoNotificationService $geoNotificationService
    ) {}

    /**
     * Update user location and check for relevant notifications
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|integer|min:0',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        try {
            $user = Auth::user();
            
            $location = $this->geoNotificationService
                ->updateUserLocationAndNotify($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'location' => $location->toMapData(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user location: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location',
            ], 500);
        }
    }

    /**
     * Get user's location history
     */
    public function getLocationHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = $request->get('limit', 10);

        $locations = $user->locations()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($location) => $location->toMapData());

        return response()->json([
            'success' => true,
            'locations' => $locations,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(GeoNotification $notification): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user should have access to this notification
        if (!$notification->shouldSendToUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark notification as clicked
     */
    public function markAsClicked(GeoNotification $notification): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user should have access to this notification
        if (!$notification->shouldSendToUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsClicked();

        return response()->json([
            'success' => true,
            'message' => 'Notification interaction tracked',
        ]);
    }

    /**
     * Get nearby notifications for current user
     */
    public function getNearbyNotifications(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = $request->get('limit', 20);

        $notifications = GeoNotification::forUserLocation($user)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) use ($user) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'location_name' => $notification->location_name,
                    'distance' => $notification->getDistanceFromUser($user),
                    'data' => $notification->data,
                    'created_at' => $notification->created_at->toISOString(),
                    'is_read' => $notification->is_read,
                    'is_clicked' => $notification->is_clicked,
                ];
            });

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Test notification - Development only
     */
    public function testNotification(Request $request): JsonResponse
    {
        if (!app()->environment('local')) {
            return response()->json([
                'success' => false,
                'message' => 'Test notifications only available in local environment',
            ], 403);
        }

        $request->validate([
            'type' => 'required|string|in:nearby_rental,pickup_reminder,area_alert,promotional',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:100|max:100000',
        ]);

        try {
            $notification = GeoNotification::create([
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius' => $request->radius ?? 5000,
                'location_name' => 'Test Location',
                'data' => [
                    'test' => true,
                    'created_by' => Auth::id(),
                ],
            ]);

            $this->geoNotificationService->processNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent',
                'notification_id' => $notification->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send test notification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
            ], 500);
        }
    }

    /**
     * Get notification statistics for admin
     */
    public function getStatistics(): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $statistics = $this->geoNotificationService->getStatistics();

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }
}