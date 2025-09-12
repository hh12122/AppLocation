<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class NotificationPreferencesController extends Controller
{
    /**
     * Show the notification preferences page
     */
    public function show(): Response
    {
        $user = Auth::user();
        $preferences = $user->notificationPreferences;
        
        // If no preferences exist, use defaults
        if (!$preferences) {
            $preferences = NotificationPreferences::getDefaultPreferences();
        } else {
            $preferences = $preferences->toArray();
        }

        return Inertia::render('Settings/NotificationPreferences', [
            'preferences' => $preferences,
        ]);
    }

    /**
     * Store or update notification preferences
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nearby_rentals' => 'boolean',
            'pickup_reminders' => 'boolean',
            'area_alerts' => 'boolean',
            'promotional' => 'boolean',
            'new_listings' => 'boolean',
            'price_drops' => 'boolean',
            'share_location' => 'boolean',
            'notification_radius' => 'integer|min:1000|max:100000',
            'quiet_hours_enabled' => 'boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'frequency' => 'in:realtime,hourly,daily,weekly',
            'max_per_day' => 'integer|min:1|max:100',
            'push_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
        ]);

        $user = Auth::user();

        // Update or create preferences
        $preferences = $user->notificationPreferences;
        
        if ($preferences) {
            $preferences->update($request->all());
        } else {
            NotificationPreferences::create(array_merge(
                ['user_id' => $user->id],
                $request->all()
            ));
        }

        return redirect()->back()->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Reset preferences to defaults
     */
    public function reset(): RedirectResponse
    {
        $user = Auth::user();
        $preferences = $user->notificationPreferences;
        
        if ($preferences) {
            $preferences->update(NotificationPreferences::getDefaultPreferences());
        } else {
            NotificationPreferences::create(array_merge(
                ['user_id' => $user->id],
                NotificationPreferences::getDefaultPreferences()
            ));
        }

        return redirect()->back()->with('success', 'Notification preferences reset to defaults.');
    }

    /**
     * Toggle a specific notification type
     */
    public function toggle(Request $request, string $type): RedirectResponse
    {
        $validTypes = [
            'nearby_rentals',
            'pickup_reminders',
            'area_alerts',
            'promotional',
            'new_listings',
            'price_drops',
            'push_enabled',
            'email_enabled',
            'sms_enabled',
            'share_location',
        ];

        if (!in_array($type, $validTypes)) {
            return redirect()->back()->withErrors(['type' => 'Invalid notification type.']);
        }

        $user = Auth::user();
        $preferences = $user->getOrCreateNotificationPreferences();
        
        $preferences->update([
            $type => !$preferences->{$type}
        ]);

        $status = $preferences->{$type} ? 'enabled' : 'disabled';
        $label = str_replace('_', ' ', $type);

        return redirect()->back()->with('success', ucfirst($label) . " notifications {$status}.");
    }

    /**
     * Get user's current location preferences
     */
    public function getLocationSettings(): array
    {
        $user = Auth::user();
        $preferences = $user->notificationPreferences;

        return [
            'share_location' => $preferences?->share_location ?? false,
            'notification_radius' => $preferences?->notification_radius ?? 10000,
            'favorite_locations' => $preferences?->favorite_locations ?? [],
        ];
    }

    /**
     * Add a favorite location
     */
    public function addFavoriteLocation(Request $request): RedirectResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $preferences = $user->getOrCreateNotificationPreferences();

        $preferences->addFavoriteLocation([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'name' => $request->name,
            'address' => $request->address,
        ]);

        return redirect()->back()->with('success', 'Favorite location added successfully.');
    }

    /**
     * Remove a favorite location
     */
    public function removeFavoriteLocation(Request $request, string $locationId): RedirectResponse
    {
        $user = Auth::user();
        $preferences = $user->notificationPreferences;

        if ($preferences) {
            $preferences->removeFavoriteLocation($locationId);
        }

        return redirect()->back()->with('success', 'Favorite location removed successfully.');
    }

    /**
     * Export user's notification preferences
     */
    public function export(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $preferences = $user->notificationPreferences;

        $data = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'preferences' => $preferences ? $preferences->toArray() : NotificationPreferences::getDefaultPreferences(),
            'exported_at' => now()->toISOString(),
        ];

        return response()->json($data);
    }
}