<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class RentalController extends Controller
{
    public function index(Request $request)
    {
        $query = Rental::with(['vehicle.images', 'vehicle.owner', 'renter']);

        if ($request->has('type')) {
            if ($request->type === 'my_rentals') {
                $query->where('renter_id', auth()->id());
            } elseif ($request->type === 'my_bookings') {
                $query->whereHas('vehicle', function ($q) {
                    $q->where('owner_id', auth()->id());
                });
            }
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $rentals = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Rentals/Index', [
            'rentals' => $rentals,
            'filters' => $request->only(['type', 'status'])
        ]);
    }

    public function create(Request $request, Vehicle $vehicle)
    {
        $user = auth()->user();
        
        if (!$user->canRent()) {
            if (!$user->driving_license_number || !$user->driving_license_expiry) {
                return redirect()->route('license.verification')
                    ->with('warning', 'Vous devez fournir les informations de votre permis de conduire avant de pouvoir louer un véhicule.');
            }
            if ($user->driving_license_expiry < now()) {
                return redirect()->route('license.verification')
                    ->with('error', 'Votre permis de conduire a expiré. Veuillez le mettre à jour.');
            }
            if ($user->driving_license_status === 'rejected') {
                return redirect()->route('license.verification')
                    ->with('error', 'Votre permis de conduire a été rejeté. Veuillez soumettre des documents valides.');
            }
        }
        
        abort_if($vehicle->owner_id === auth()->id(), 403, 'Vous ne pouvez pas louer votre propre véhicule.');
        abort_if(!$vehicle->is_available || $vehicle->status !== 'active', 403, 'Ce véhicule n\'est pas disponible.');

        $vehicle->load(['images', 'owner']);

        return Inertia::render('Rentals/Create', [
            'vehicle' => $vehicle,
            'startDate' => $request->get('start_date'),
            'endDate' => $request->get('end_date')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'special_requests' => 'nullable|string|max:1000'
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        abort_if(!auth()->user()->canRent(), 403);
        abort_if($vehicle->owner_id === auth()->id(), 403);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        if (!$vehicle->isAvailableForPeriod($startDate, $endDate)) {
            return back()->withErrors(['dates' => 'Le véhicule n\'est pas disponible pour ces dates.']);
        }

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $totalAmount = $vehicle->calculateTotalPrice($startDate, $endDate);

        $rental = Rental::create([
            'vehicle_id' => $vehicle->id,
            'renter_id' => auth()->id(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'pending',
            'total_amount' => $totalAmount,
            'daily_rate' => $vehicle->daily_rate,
            'total_days' => $totalDays,
            'deposit' => $totalAmount * 0.2, // 20% deposit
            'special_requests' => $validated['special_requests']
        ]);

        return redirect()->route('rentals.show', $rental)
            ->with('success', 'Demande de location envoyée avec succès !');
    }

    public function show(Rental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['vehicle.images', 'vehicle.owner', 'renter', 'reviews']);

        return Inertia::render('Rentals/Show', [
            'rental' => $rental,
            'canReview' => $rental->canBeReviewed() && !$rental->reviews()->where('reviewer_id', auth()->id())->exists()
        ]);
    }

    public function confirm(Rental $rental)
    {
        $this->authorize('confirm', $rental);

        if ($rental->status !== 'pending') {
            return back()->withErrors(['status' => 'Cette réservation ne peut plus être confirmée.']);
        }

        $rental->update(['status' => 'confirmed']);

        return back()->with('success', 'Réservation confirmée avec succès !');
    }

    public function cancel(Rental $rental)
    {
        $this->authorize('cancel', $rental);

        if (!in_array($rental->status, ['pending', 'confirmed'])) {
            return back()->withErrors(['status' => 'Cette réservation ne peut plus être annulée.']);
        }

        $rental->update(['status' => 'cancelled']);

        return back()->with('success', 'Réservation annulée avec succès !');
    }

    public function pickup(Request $request, Rental $rental)
    {
        $this->authorize('pickup', $rental);

        $validated = $request->validate([
            'pickup_mileage' => 'required|integer|min:0',
            'pickup_notes' => 'nullable|string|max:1000',
            'pickup_images' => 'nullable|array|max:5',
            'pickup_images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $pickupImages = [];
        if ($request->hasFile('pickup_images')) {
            foreach ($request->file('pickup_images') as $image) {
                $path = $image->store('rentals/pickup', 'public');
                $pickupImages[] = $path;
            }
        }

        $rental->update([
            'status' => 'active',
            'pickup_datetime' => now(),
            'pickup_mileage' => $validated['pickup_mileage'],
            'pickup_notes' => $validated['pickup_notes'],
            'pickup_images' => $pickupImages
        ]);

        return back()->with('success', 'Remise du véhicule enregistrée avec succès !');
    }

    public function return(Request $request, Rental $rental)
    {
        $this->authorize('return', $rental);

        $validated = $request->validate([
            'return_mileage' => 'required|integer|min:' . $rental->pickup_mileage,
            'return_notes' => 'nullable|string|max:1000',
            'return_images' => 'nullable|array|max:5',
            'return_images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $returnImages = [];
        if ($request->hasFile('return_images')) {
            foreach ($request->file('return_images') as $image) {
                $path = $image->store('rentals/return', 'public');
                $returnImages[] = $path;
            }
        }

        $rental->update([
            'status' => 'completed',
            'return_datetime' => now(),
            'return_mileage' => $validated['return_mileage'],
            'return_notes' => $validated['return_notes'],
            'return_images' => $returnImages
        ]);

        // Update vehicle mileage
        $rental->vehicle->update([
            'mileage' => $validated['return_mileage']
        ]);

        return back()->with('success', 'Retour du véhicule enregistré avec succès !');
    }

    public function myRentals()
    {
        $rentals = Rental::where('renter_id', auth()->id())
            ->with(['vehicle.images', 'vehicle.owner'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Rentals/MyRentals', [
            'rentals' => $rentals
        ]);
    }

    public function myBookings()
    {
        $rentals = Rental::whereHas('vehicle', function ($query) {
                $query->where('owner_id', auth()->id());
            })
            ->with(['vehicle.images', 'renter'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Rentals/MyBookings', [
            'rentals' => $rentals
        ]);
    }
}
