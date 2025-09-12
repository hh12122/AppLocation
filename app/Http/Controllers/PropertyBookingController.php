<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class PropertyBookingController extends Controller
{
    /**
     * Show booking form for a property.
     */
    public function create(Property $property, Request $request)
    {
        if (!$property->is_available || $property->status !== 'active') {
            return redirect()->route('properties.show', $property)
                ->with('error', 'Cette propriété n\'est pas disponible à la réservation.');
        }

        $checkinDate = $request->checkin ? Carbon::parse($request->checkin) : null;
        $checkoutDate = $request->checkout ? Carbon::parse($request->checkout) : null;
        $guests = max(1, (int)$request->guests);

        // Validate dates if provided
        if ($checkinDate && $checkoutDate) {
            if ($checkinDate <= now()->toDateString()) {
                return redirect()->route('properties.show', $property)
                    ->with('error', 'La date d\'arrivée doit être dans le futur.');
            }

            if ($checkoutDate <= $checkinDate) {
                return redirect()->route('properties.show', $property)
                    ->with('error', 'La date de départ doit être après la date d\'arrivée.');
            }

            if (!$property->isAvailableForDates($checkinDate, $checkoutDate)) {
                return redirect()->route('properties.show', $property)
                    ->with('error', 'Cette propriété n\'est pas disponible pour ces dates.');
            }

            // Calculate pricing
            $pricing = $property->calculateTotalPrice($checkinDate, $checkoutDate, $guests);
        }

        $property->load(['owner', 'images' => function ($query) {
            $query->orderBy('sort_order')->limit(3);
        }]);

        return Inertia::render('Properties/Book', [
            'property' => $property,
            'initialData' => [
                'checkin_date' => $checkinDate?->format('Y-m-d'),
                'checkout_date' => $checkoutDate?->format('Y-m-d'),
                'guests' => $guests,
                'pricing' => $pricing ?? null,
            ],
            'availableCredits' => Auth::user()->getAvailableCredits(),
            'referralStats' => Auth::user()->getReferralStats(),
        ]);
    }

    /**
     * Store a new booking.
     */
    public function store(Request $request, Property $property)
    {
        $validated = $request->validate([
            'checkin_date' => 'required|date|after:today',
            'checkout_date' => 'required|date|after:checkin_date',
            'guests_count' => 'required|integer|min:1|max:' . $property->max_guests,
            'adults_count' => 'required|integer|min:1',
            'children_count' => 'nullable|integer|min:0',
            'infants_count' => 'nullable|integer|min:0',
            'special_requests' => 'nullable|string|max:1000',
            'purpose_of_trip' => 'nullable|string|max:500',
            'guest_details' => 'nullable|array',
        ]);

        $checkinDate = Carbon::parse($validated['checkin_date']);
        $checkoutDate = Carbon::parse($validated['checkout_date']);

        // Verify property is still available
        if (!$property->isAvailableForDates($checkinDate, $checkoutDate)) {
            return back()->withErrors(['dates' => 'Cette propriété n\'est plus disponible pour ces dates.']);
        }

        // Calculate pricing
        $pricing = $property->calculateTotalPrice($checkinDate, $checkoutDate, $validated['guests_count']);

        // Create booking
        $booking = PropertyBooking::create([
            'property_id' => $property->id,
            'guest_id' => Auth::id(),
            'checkin_date' => $checkinDate,
            'checkout_date' => $checkoutDate,
            'nights_count' => $pricing['nights'],
            'guests_count' => $validated['guests_count'],
            'adults_count' => $validated['adults_count'],
            'children_count' => $validated['children_count'] ?? 0,
            'infants_count' => $validated['infants_count'] ?? 0,
            'nightly_rate' => $pricing['nightly_rate'],
            'subtotal' => $pricing['subtotal'],
            'cleaning_fee' => $pricing['cleaning_fee'],
            'service_fee' => $pricing['service_fee'],
            'tax_amount' => $pricing['tax_amount'],
            'total_amount' => $pricing['total_amount'],
            'security_deposit' => $pricing['security_deposit'],
            'host_payout' => $pricing['total_amount'] - $pricing['service_fee'], // Host gets total minus platform fee
            'special_requests' => $validated['special_requests'],
            'purpose_of_trip' => $validated['purpose_of_trip'],
            'guest_details' => $validated['guest_details'],
            'status' => $property->instant_booking ? 'confirmed' : 'pending',
        ]);

        if ($property->instant_booking) {
            $booking->confirm();
            $message = 'Réservation confirmée instantanément !';
        } else {
            $message = 'Demande de réservation envoyée. L\'hôte a 24h pour répondre.';
        }

        // Redirect to payment if confirmed
        if ($booking->status === 'confirmed') {
            return redirect()->route('property-payments.show', $booking)
                ->with('success', $message);
        }

        return redirect()->route('property-bookings.show', $booking)
            ->with('success', $message);
    }

    /**
     * Show a booking.
     */
    public function show(PropertyBooking $booking)
    {
        if ($booking->guest_id !== Auth::id() && $booking->property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to view this booking');
        }

        $booking->load([
            'property.owner',
            'property.images' => function ($query) {
                $query->orderBy('sort_order')->limit(3);
            },
            'guest',
        ]);

        return Inertia::render('Properties/BookingDetails', [
            'booking' => $booking,
            'isOwner' => $booking->property->owner_id === Auth::id(),
        ]);
    }

    /**
     * Confirm a booking (for hosts).
     */
    public function confirm(PropertyBooking $booking)
    {
        if ($booking->property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to confirm this booking');
        }

        if (!$booking->canBeConfirmed()) {
            return back()->with('error', 'Cette réservation ne peut plus être confirmée.');
        }

        $booking->confirm();

        return back()->with('success', 'Réservation confirmée ! Le voyageur va recevoir une notification.');
    }

    /**
     * Cancel a booking.
     */
    public function cancel(PropertyBooking $booking, Request $request)
    {
        $isOwner = $booking->property->owner_id === Auth::id();
        $isGuest = $booking->guest_id === Auth::id();

        if (!$isOwner && !$isGuest) {
            abort(403, 'Unauthorized to cancel this booking');
        }

        if (!$booking->canBeCancelled()) {
            return back()->with('error', 'Cette réservation ne peut plus être annulée.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $cancelledBy = $isOwner ? 'host' : 'guest';
        $booking->cancel($validated['reason'], $cancelledBy);

        // Calculate refund if applicable
        if ($isGuest && $booking->payment_status === 'paid') {
            $refundAmount = $booking->calculateRefund();
            $booking->update(['refund_amount' => $refundAmount]);
        }

        $message = $isOwner 
            ? 'Réservation annulée. Le voyageur sera notifié et remboursé si applicable.'
            : 'Réservation annulée. Vous serez remboursé selon la politique d\'annulation.';

        return back()->with('success', $message);
    }

    /**
     * Check-in a guest.
     */
    public function checkIn(PropertyBooking $booking, Request $request)
    {
        if ($booking->property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to check-in this booking');
        }

        if (!$booking->canCheckIn()) {
            return back()->with('error', 'L\'arrivée ne peut pas être effectuée pour cette réservation.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'damage_report' => 'nullable|string|max:1000',
            'late_arrival' => 'boolean',
        ]);

        $booking->checkIn([
            'notes' => $validated['notes'] ?? null,
            'damage_report' => $validated['damage_report'] ?? null,
            'late_arrival' => $validated['late_arrival'] ?? false,
            'checked_in_by' => Auth::id(),
        ]);

        return back()->with('success', 'Arrivée enregistrée avec succès !');
    }

    /**
     * Check-out a guest.
     */
    public function checkOut(PropertyBooking $booking, Request $request)
    {
        if ($booking->property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to check-out this booking');
        }

        if (!$booking->canCheckOut()) {
            return back()->with('error', 'Le départ ne peut pas être effectué pour cette réservation.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'damage_report' => 'nullable|string|max:1000',
            'cleaning_status' => 'nullable|in:excellent,good,needs_attention,poor',
            'early_departure' => 'boolean',
        ]);

        $booking->checkOut([
            'notes' => $validated['notes'] ?? null,
            'damage_report' => $validated['damage_report'] ?? null,
            'cleaning_status' => $validated['cleaning_status'] ?? 'good',
            'early_departure' => $validated['early_departure'] ?? false,
            'checked_out_by' => Auth::id(),
        ]);

        return back()->with('success', 'Départ enregistré avec succès !');
    }

    /**
     * Get user's bookings.
     */
    public function myBookings(Request $request)
    {
        $bookings = Auth::user()
            ->propertyBookings()
            ->with(['property.primaryImage', 'property.owner'])
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->query());

        return Inertia::render('Properties/MyBookings', [
            'bookings' => $bookings,
            'stats' => [
                'total' => Auth::user()->propertyBookings()->count(),
                'upcoming' => Auth::user()->propertyBookings()->upcoming()->count(),
                'current' => Auth::user()->propertyBookings()->current()->count(),
                'completed' => Auth::user()->propertyBookings()->completed()->count(),
            ],
        ]);
    }

    /**
     * Get property bookings for owners.
     */
    public function propertyBookings(Request $request)
    {
        $bookings = PropertyBooking::byOwner(Auth::id())
            ->with(['property.primaryImage', 'guest'])
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->property_id, function ($query) use ($request) {
                $query->where('property_id', $request->property_id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->query());

        $properties = Auth::user()->properties()->select('id', 'title')->get();

        return Inertia::render('Properties/PropertyBookings', [
            'bookings' => $bookings,
            'properties' => $properties,
            'stats' => [
                'total' => Auth::user()->getTotalPropertyBookings(),
                'pending' => PropertyBooking::byOwner(Auth::id())->pending()->count(),
                'active' => PropertyBooking::byOwner(Auth::id())->active()->count(),
                'completed' => PropertyBooking::byOwner(Auth::id())->completed()->count(),
            ],
        ]);
    }
}