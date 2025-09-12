<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentBooking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class EquipmentBookingController extends Controller
{
    public function create(Equipment $equipment): Response
    {
        $equipment->load(['owner:id,name,email,rating,rating_count', 'images']);
        
        // Check if user can book equipment
        if (!Auth::user()->hasValidLicense()) {
            return Inertia::render('EquipmentBookings/Create', [
                'equipment' => $equipment,
                'licenseRequired' => true
            ]);
        }
        
        return Inertia::render('EquipmentBookings/Create', [
            'equipment' => $equipment
        ]);
    }
    
    public function store(Request $request, Equipment $equipment): RedirectResponse
    {
        $user = Auth::user();
        
        // Check if user has valid license
        if (!$user->hasValidLicense()) {
            return redirect()->route('license.verification')
                ->with('error', 'Vous devez avoir un permis de conduire vérifié pour louer du matériel.');
        }
        
        // Check if equipment is available
        if (!$equipment->is_available) {
            return back()->with('error', 'Ce matériel n\'est plus disponible.');
        }
        
        $validated = $request->validate([
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'pickup_location' => 'nullable|string|max:500',
            'special_requests' => 'nullable|string|max:1000',
            'quantity' => 'required|integer|min:1|max:' . $equipment->max_quantity,
            'rental_unit' => 'required|in:hour,day,week,month'
        ]);
        
        // Calculate duration and total price
        $startDate = new \DateTime($validated['start_date']);
        $endDate = new \DateTime($validated['end_date']);
        
        $duration = $this->calculateDuration($startDate, $endDate, $validated['rental_unit']);
        $basePrice = $equipment->getPrice($validated['rental_unit']);
        $totalPrice = $basePrice * $duration * $validated['quantity'];
        
        // Apply discounts if any
        $discountPercent = $this->calculateDiscount($duration, $validated['rental_unit']);
        $discountAmount = $totalPrice * ($discountPercent / 100);
        $finalPrice = $totalPrice - $discountAmount;
        
        // Calculate fees
        $platformFee = $finalPrice * 0.05; // 5% platform fee
        $totalWithFees = $finalPrice + $platformFee;
        
        $booking = EquipmentBooking::create([
            'equipment_id' => $equipment->id,
            'renter_id' => $user->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'pickup_location' => $validated['pickup_location'],
            'special_requests' => $validated['special_requests'],
            'quantity' => $validated['quantity'],
            'rental_unit' => $validated['rental_unit'],
            'duration' => $duration,
            'base_price' => $basePrice,
            'total_price' => $finalPrice,
            'platform_fee' => $platformFee,
            'total_with_fees' => $totalWithFees,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'owner_payout' => $finalPrice - ($finalPrice * 0.05), // Owner gets 95%
            'status' => 'pending'
        ]);
        
        return redirect()->route('equipment-bookings.show', $booking)
            ->with('success', 'Votre demande de réservation a été envoyée au propriétaire.');
    }
    
    public function show(EquipmentBooking $booking): Response
    {
        $user = Auth::user();
        
        // Check if user can view this booking
        if ($booking->renter_id !== $user->id && $booking->equipment->owner_id !== $user->id && !$user->is_admin) {
            abort(403);
        }
        
        $booking->load([
            'equipment:id,name,category,description,owner_id',
            'equipment.owner:id,name,email,phone,rating,rating_count',
            'equipment.images',
            'renter:id,name,email,phone,rating,rating_count'
        ]);
        
        return Inertia::render('EquipmentBookings/Show', [
            'booking' => $booking
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
            'confirmed_at' => now()
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
        
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Cette réservation ne peut plus être annulée.');
        }
        
        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500'
        ]);
        
        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $validated['cancellation_reason'] ?? null
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
            'ready_at' => now()
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
            'delivered_at' => now()
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
            'returned_at' => now()
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
            'new_end_date' => 'required|date|after:' . $booking->end_date,
            'extension_reason' => 'nullable|string|max:500'
        ]);
        
        $booking->update([
            'extension_requested' => true,
            'requested_end_date' => $validated['new_end_date'],
            'extension_reason' => $validated['extension_reason'],
            'extension_requested_at' => now()
        ]);
        
        return back()->with('success', 'Demande d\'extension envoyée au propriétaire.');
    }
    
    public function myBookings(): Response
    {
        $user = Auth::user();
        
        $bookings = EquipmentBooking::with([
            'equipment:id,name,category,owner_id',
            'equipment.owner:id,name',
            'equipment.images'
        ])
        ->where('renter_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->paginate(10);
        
        return Inertia::render('EquipmentBookings/MyBookings', [
            'bookings' => $bookings
        ]);
    }
    
    public function equipmentBookings(): Response
    {
        $user = Auth::user();
        
        $bookings = EquipmentBooking::with([
            'equipment:id,name,category,owner_id',
            'renter:id,name,rating,rating_count'
        ])
        ->whereHas('equipment', function ($query) use ($user) {
            $query->where('owner_id', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(10);
        
        return Inertia::render('EquipmentBookings/EquipmentBookings', [
            'bookings' => $bookings
        ]);
    }
    
    private function calculateDuration(\DateTime $startDate, \DateTime $endDate, string $unit): int
    {
        $interval = $startDate->diff($endDate);
        
        return match($unit) {
            'hour' => ($interval->days * 24) + $interval->h,
            'day' => max(1, $interval->days),
            'week' => max(1, ceil($interval->days / 7)),
            'month' => max(1, $interval->m + ($interval->y * 12)),
            default => 1
        };
    }
    
    private function calculateDiscount(int $duration, string $unit): float
    {
        // Apply bulk discounts based on duration
        return match($unit) {
            'day' => $duration >= 7 ? 10 : ($duration >= 3 ? 5 : 0),
            'week' => $duration >= 4 ? 15 : ($duration >= 2 ? 8 : 0),
            'month' => $duration >= 3 ? 20 : 0,
            default => 0
        };
    }
}