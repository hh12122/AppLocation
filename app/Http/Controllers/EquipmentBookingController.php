<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentBookingController extends Controller
{
    public function create(Equipment $equipment): Response|RedirectResponse
    {
        $equipment->load(['owner:id,name,email,rating,rating_count', 'images']);

        // Prevent booking own equipment
        if ($equipment->owner_id === Auth::id()) {
            return redirect()->route('equipment.show', $equipment)
                ->with('error', 'Vous ne pouvez pas réserver votre propre matériel.');
        }

        $categoryConfig = Equipment::getCategoryConfig()[$equipment->category] ?? null;

        // Check if user can book equipment
        if (! Auth::user()->hasValidLicense()) {
            return Inertia::render('equipment-bookings/Create', [
                'equipment' => $equipment,
                'categoryConfig' => $categoryConfig,
                'licenseRequired' => true,
            ]);
        }

        return Inertia::render('equipment-bookings/Create', [
            'equipment' => $equipment,
            'categoryConfig' => $categoryConfig,
        ]);
    }

    public function store(Request $request, Equipment $equipment): RedirectResponse
    {
        $user = Auth::user();

        if ($equipment->owner_id === $user->id) {
            return back()->with('error', 'Vous ne pouvez pas réserver votre propre matériel.');
        }

        if (! $user->hasValidLicense()) {
            return redirect()->route('license.verification')
                ->with('error', 'Vous devez avoir un permis de conduire vérifié pour louer du matériel.');
        }

        if (! $equipment->is_available) {
            return back()->with('error', 'Ce matériel n\'est plus disponible.');
        }

        $validated = $request->validate([
            'start_datetime' => 'required|date|after:now',
            'end_datetime' => 'required|date|after:start_datetime',
            'duration_unit' => 'required|in:hour,day,week,month',
            'pickup_address' => 'nullable|string|max:500',
            'delivery_address' => 'nullable|string|max:500',
            'special_requests' => 'nullable|string|max:1000',
            'usage_purpose' => 'nullable|string|max:500',
        ]);

        $startDate = Carbon::parse($validated['start_datetime']);
        $endDate = Carbon::parse($validated['end_datetime']);
        $unit = $validated['duration_unit'];

        $duration = $this->calculateDuration($startDate, $endDate, $unit);
        $unitRate = $equipment->{($unit === 'hour' ? 'hourly_rate' : ($unit === 'day' ? 'daily_rate' : ($unit === 'week' ? 'weekly_rate' : 'monthly_rate')))} ?? 0;
        $subtotal = $unitRate * $duration;

        $serviceFee = $subtotal * 0.05;
        $cleaningFee = $equipment->cleaning_fee ?? 0;
        $deliveryFee = $validated['delivery_address'] ? ($equipment->delivery_fee ?? 0) : 0;
        $totalAmount = $subtotal + $serviceFee + $cleaningFee + $deliveryFee;
        $ownerPayout = $subtotal - $serviceFee;

        $booking = EquipmentBooking::create([
            'equipment_id' => $equipment->id,
            'renter_id' => $user->id,
            'start_datetime' => $startDate,
            'end_datetime' => $endDate,
            'duration_value' => $duration,
            'duration_unit' => $unit,
            'unit_rate' => $unitRate,
            'subtotal' => $subtotal,
            'security_deposit' => $equipment->security_deposit ?? 0,
            'cleaning_fee' => $cleaningFee,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $serviceFee,
            'total_amount' => $totalAmount,
            'owner_payout' => $ownerPayout,
            'fulfillment_type' => $validated['delivery_address'] ? 'delivery' : 'pickup',
            'pickup_address' => $validated['pickup_address'] ?? $equipment->address,
            'delivery_address' => $validated['delivery_address'],
            'special_requests' => $validated['special_requests'],
            'usage_purpose' => $validated['usage_purpose'],
            'status' => $equipment->instant_booking ? 'confirmed' : 'pending',
            'payment_status' => 'pending',
        ]);

        $message = $equipment->instant_booking
            ? 'Réservation confirmée instantanément !'
            : 'Votre demande de réservation a été envoyée au propriétaire.';

        return redirect()->route('equipment-bookings.show', $booking)
            ->with('success', $message);
    }

    public function show(EquipmentBooking $booking): Response
    {
        $user = Auth::user();

        // Check if user can view this booking
        if ($booking->renter_id !== $user->id && $booking->equipment->owner_id !== $user->id && ! $user->is_admin) {
            abort(403);
        }

        $booking->load([
            'equipment:id,name,category,description,owner_id',
            'equipment.owner:id,name,email,phone,rating,rating_count',
            'equipment.images',
            'renter:id,name,email,phone,rating,rating_count',
        ]);

        return Inertia::render('equipment-bookings/Show', [
            'booking' => $booking,
        ]);
    }

    public function confirm(Request $request, EquipmentBooking $booking): RedirectResponse
    {
        $user = Auth::user();

        // Only equipment owner can confirm
        if ($booking->equipment->owner_id !== $user->id) {
            abort(403);
        }

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Cette réservation ne peut plus être confirmée.');
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Réservation confirmée. Le locataire a été notifié.');
    }

    public function cancel(Request $request, EquipmentBooking $booking): RedirectResponse
    {
        $user = Auth::user();

        // Both renter and owner can cancel, but with different rules
        if ($booking->renter_id !== $user->id && $booking->equipment->owner_id !== $user->id) {
            abort(403);
        }

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Cette réservation ne peut plus être annulée.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
        ]);

        return back()->with('success', 'Réservation annulée.');
    }

    public function markAsReady(EquipmentBooking $booking): RedirectResponse
    {
        $user = Auth::user();

        // Only equipment owner can mark as ready
        if ($booking->equipment->owner_id !== $user->id) {
            abort(403);
        }

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Cette réservation doit être confirmée d\'abord.');
        }

        $booking->update([
            'status' => 'ready_for_pickup',
            'ready_at' => now(),
        ]);

        return back()->with('success', 'Matériel marqué comme prêt pour le retrait.');
    }

    public function markAsDelivered(EquipmentBooking $booking): RedirectResponse
    {
        $user = Auth::user();

        // Only equipment owner can mark as delivered
        if ($booking->equipment->owner_id !== $user->id) {
            abort(403);
        }

        if ($booking->status !== 'ready_for_pickup') {
            return back()->with('error', 'Le matériel doit être prêt pour le retrait d\'abord.');
        }

        $booking->update([
            'status' => 'in_use',
            'delivered_at' => now(),
        ]);

        return back()->with('success', 'Matériel marqué comme livré/retiré.');
    }

    public function markAsReturned(EquipmentBooking $booking): RedirectResponse
    {
        $user = Auth::user();

        // Only equipment owner can mark as returned
        if ($booking->equipment->owner_id !== $user->id) {
            abort(403);
        }

        if ($booking->status !== 'in_use') {
            return back()->with('error', 'Le matériel doit être en cours d\'utilisation.');
        }

        $booking->update([
            'status' => 'completed',
            'returned_at' => now(),
        ]);

        return back()->with('success', 'Matériel marqué comme rendu. La location est terminée.');
    }

    public function requestExtension(Request $request, EquipmentBooking $booking): RedirectResponse
    {
        $user = Auth::user();

        // Only renter can request extension
        if ($booking->renter_id !== $user->id) {
            abort(403);
        }

        if ($booking->status !== 'in_use') {
            return back()->with('error', 'Vous ne pouvez demander une extension que pendant l\'utilisation.');
        }

        $validated = $request->validate([
            'new_end_date' => 'required|date|after:'.$booking->end_date,
            'extension_reason' => 'nullable|string|max:500',
        ]);

        $booking->update([
            'extension_requested' => true,
            'requested_end_date' => $validated['new_end_date'],
            'extension_reason' => $validated['extension_reason'],
            'extension_requested_at' => now(),
        ]);

        return back()->with('success', 'Demande d\'extension envoyée au propriétaire.');
    }

    public function myBookings(): Response
    {
        $user = Auth::user();

        $bookings = EquipmentBooking::with([
            'equipment:id,name,category,owner_id',
            'equipment.owner:id,name',
            'equipment.images',
        ])
            ->where('renter_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('equipment-bookings/MyBookings', [
            'bookings' => $bookings,
        ]);
    }

    public function equipmentBookings(): Response
    {
        $user = Auth::user();

        $bookings = EquipmentBooking::with([
            'equipment:id,name,category,owner_id',
            'equipment.images',
            'renter:id,name,rating,rating_count',
        ])
            ->whereHas('equipment', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $statsRaw = EquipmentBooking::whereHas('equipment', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'in_use' THEN 1 END) as active,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
        ")->first();

        $stats = [
            'total' => $statsRaw->total ?? 0,
            'pending' => $statsRaw->pending ?? 0,
            'active' => $statsRaw->active ?? 0,
            'completed' => $statsRaw->completed ?? 0,
        ];

        return Inertia::render('equipment-bookings/EquipmentBookings', [
            'bookings' => $bookings,
            'stats' => $stats,
        ]);
    }

    private function calculateDuration(Carbon $startDate, Carbon $endDate, string $unit): int
    {
        $diffInHours = $startDate->diffInHours($endDate);

        return match ($unit) {
            'hour' => max(1, $diffInHours),
            'day' => max(1, $startDate->diffInDays($endDate)),
            'week' => max(1, ceil($startDate->diffInDays($endDate) / 7)),
            'month' => max(1, $startDate->diffInMonths($endDate)),
            default => 1
        };
    }

    private function calculateDiscount(int $duration, string $unit): float
    {
        // Apply bulk discounts based on duration
        return match ($unit) {
            'day' => $duration >= 7 ? 10 : ($duration >= 3 ? 5 : 0),
            'week' => $duration >= 4 ? 15 : ($duration >= 2 ? 8 : 0),
            'month' => $duration >= 3 ? 20 : 0,
            default => 0
        };
    }
}
