<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeoNotification;
use App\Models\User;
use App\Services\GeoNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeoNotificationController extends Controller
{
    public function __construct(
        private readonly GeoNotificationService $geoNotificationService
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display the geo-notifications dashboard
     */
    public function index(Request $request): Response
    {
        $query = GeoNotification::with('user:id,name,email')
            ->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%");
            });
        }

        $notifications = $query->paginate(20)->withQueryString();

        // Get statistics
        $statistics = $this->geoNotificationService->getStatistics();

        return Inertia::render('Admin/GeoNotifications/Index', [
            'notifications' => $notifications,
            'statistics' => $statistics,
            'filters' => $request->only(['status', 'type', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new notification
     */
    public function create(): Response
    {
        return Inertia::render('Admin/GeoNotifications/Create', [
            'types' => $this->getNotificationTypes(),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    /**
     * Store a newly created notification
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'required|string|in:nearby_rental,pickup_reminder,area_alert,promotional,new_listing,price_drop',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:100|max:100000',
            'location_name' => 'nullable|string|max:255',
            'target_criteria' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
            'scheduled_for' => 'nullable|date|after:now',
            'data' => 'nullable|array',
        ]);

        $notification = GeoNotification::create($request->all());

        // If not scheduled, process immediately
        if (!$request->scheduled_for) {
            $this->geoNotificationService->processNotification($notification);
        }

        return redirect()->route('admin.geo-notifications.index')
            ->with('success', 'Geo-notification created and sent successfully.');
    }

    /**
     * Display the specified notification
     */
    public function show(GeoNotification $geoNotification): Response
    {
        $geoNotification->load('user:id,name,email');

        // Get eligible users count
        $eligibleUsersCount = $this->geoNotificationService
            ->getEligibleUsers($geoNotification)
            ->count();

        return Inertia::render('Admin/GeoNotifications/Show', [
            'notification' => $geoNotification,
            'eligibleUsersCount' => $eligibleUsersCount,
        ]);
    }

    /**
     * Show the form for editing the specified notification
     */
    public function edit(GeoNotification $geoNotification): Response
    {
        return Inertia::render('Admin/GeoNotifications/Edit', [
            'notification' => $geoNotification,
            'types' => $this->getNotificationTypes(),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    /**
     * Update the specified notification
     */
    public function update(Request $request, GeoNotification $geoNotification): RedirectResponse
    {
        $request->validate([
            'type' => 'required|string|in:nearby_rental,pickup_reminder,area_alert,promotional,new_listing,price_drop',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:100|max:100000',
            'location_name' => 'nullable|string|max:255',
            'target_criteria' => 'nullable|array',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'scheduled_for' => 'nullable|date',
            'data' => 'nullable|array',
        ]);

        $geoNotification->update($request->all());

        return redirect()->route('admin.geo-notifications.index')
            ->with('success', 'Geo-notification updated successfully.');
    }

    /**
     * Remove the specified notification
     */
    public function destroy(GeoNotification $geoNotification): RedirectResponse
    {
        $geoNotification->delete();

        return redirect()->route('admin.geo-notifications.index')
            ->with('success', 'Geo-notification deleted successfully.');
    }

    /**
     * Activate a notification
     */
    public function activate(GeoNotification $geoNotification): RedirectResponse
    {
        $geoNotification->update(['is_active' => true]);

        return redirect()->back()
            ->with('success', 'Notification activated successfully.');
    }

    /**
     * Deactivate a notification
     */
    public function deactivate(GeoNotification $geoNotification): RedirectResponse
    {
        $geoNotification->update(['is_active' => false]);

        return redirect()->back()
            ->with('success', 'Notification deactivated successfully.');
    }

    /**
     * Process a pending notification
     */
    public function process(GeoNotification $geoNotification): RedirectResponse
    {
        if ($geoNotification->status !== 'pending') {
            return redirect()->back()
                ->withErrors(['notification' => 'Only pending notifications can be processed.']);
        }

        $this->geoNotificationService->processNotification($geoNotification);

        return redirect()->back()
            ->with('success', 'Notification processed successfully.');
    }

    /**
     * Send a test notification
     */
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:nearby_rental,pickup_reminder,area_alert,promotional,new_listing,price_drop',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:100|max:100000',
        ]);

        try {
            $notification = GeoNotification::create([
                'type' => $request->type,
                'title' => '[TEST] ' . $request->title,
                'message' => $request->message,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius' => $request->radius ?? 5000,
                'location_name' => 'Test Location',
                'data' => [
                    'test' => true,
                    'created_by_admin' => auth()->id(),
                ],
                'expires_at' => now()->addMinutes(30), // Test notifications expire in 30 minutes
            ]);

            $this->geoNotificationService->processNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully.',
                'notification_id' => $notification->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification statistics for dashboard
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->geoNotificationService->getStatistics();

        return response()->json($statistics);
    }

    /**
     * Bulk actions on notifications
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,process',
            'notification_ids' => 'required|array|min:1',
            'notification_ids.*' => 'exists:geo_notifications,id',
        ]);

        $notifications = GeoNotification::whereIn('id', $request->notification_ids);
        $count = $notifications->count();

        switch ($request->action) {
            case 'activate':
                $notifications->update(['is_active' => true]);
                $message = "{$count} notifications activated successfully.";
                break;

            case 'deactivate':
                $notifications->update(['is_active' => false]);
                $message = "{$count} notifications deactivated successfully.";
                break;

            case 'delete':
                $notifications->delete();
                $message = "{$count} notifications deleted successfully.";
                break;

            case 'process':
                $notifications->where('status', 'pending')->each(function ($notification) {
                    $this->geoNotificationService->processNotification($notification);
                });
                $message = "Pending notifications processed successfully.";
                break;

            default:
                return redirect()->back()
                    ->withErrors(['action' => 'Invalid action.']);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Clean up old notifications
     */
    public function cleanup(): RedirectResponse
    {
        $this->geoNotificationService->cleanup();

        return redirect()->back()
            ->with('success', 'Old notifications cleaned up successfully.');
    }

    /**
     * Get notification types for dropdowns
     */
    private function getNotificationTypes(): array
    {
        return [
            'nearby_rental' => 'Nearby Rental',
            'pickup_reminder' => 'Pickup Reminder',
            'area_alert' => 'Area Alert',
            'promotional' => 'Promotional',
            'new_listing' => 'New Listing',
            'price_drop' => 'Price Drop',
        ];
    }

    /**
     * Get status options for dropdowns
     */
    private function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'sent' => 'Sent',
            'read' => 'Read',
            'clicked' => 'Clicked',
            'failed' => 'Failed',
        ];
    }
}