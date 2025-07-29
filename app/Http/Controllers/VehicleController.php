<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with(['owner', 'images'])
            ->where('status', 'active')
            ->where('is_available', true);

        // Filters
        if ($request->has('brand') && $request->brand) {
            $query->where('brand', 'like', '%' . $request->brand . '%');
        }

        if ($request->has('city') && $request->city) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->has('fuel_type') && $request->fuel_type) {
            $query->where('fuel_type', $request->fuel_type);
        }

        if ($request->has('transmission') && $request->transmission) {
            $query->where('transmission', $request->transmission);
        }

        if ($request->has('min_seats') && $request->min_seats) {
            $query->where('seats', '>=', $request->min_seats);
        }

        if ($request->has('max_price') && $request->max_price) {
            $query->where('daily_rate', '<=', $request->max_price);
        }

        // Check availability for specific dates
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            
            $query->whereDoesntHave('rentals', function ($q) use ($startDate, $endDate) {
                $q->whereIn('status', ['confirmed', 'active'])
                  ->where(function ($subQuery) use ($startDate, $endDate) {
                      $subQuery->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($nestedQuery) use ($startDate, $endDate) {
                              $nestedQuery->where('start_date', '<=', $startDate)
                                         ->where('end_date', '>=', $endDate);
                          });
                  });
            });
        }

        $vehicles = $query->orderBy('created_at', 'desc')->paginate(12);

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only(['brand', 'city', 'fuel_type', 'transmission', 'min_seats', 'max_price', 'start_date', 'end_date'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Vehicles/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1950|max:' . (date('Y') + 1),
            'color' => 'required|string|max:255',
            'license_plate' => 'required|string|max:255|unique:vehicles',
            'mileage' => 'required|integer|min:0',
            'fuel_type' => 'required|in:gasoline,diesel,electric,hybrid',
            'transmission' => 'required|in:manual,automatic',
            'seats' => 'required|integer|min:1|max:9',
            'doors' => 'required|integer|min:2|max:5',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'daily_rate' => 'required|numeric|min:1',
            'weekly_rate' => 'nullable|numeric|min:1',
            'monthly_rate' => 'nullable|numeric|min:1',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $validated['owner_id'] = auth()->id();

        $vehicle = Vehicle::create($validated);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('vehicles', 'public');
                
                $vehicle->images()->create([
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index
                ]);
            }
        }

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule ajouté avec succès !');
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['owner', 'images', 'reviews.reviewer']);
        
        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'canRent' => auth()->check() && auth()->user()->canRent() && $vehicle->owner_id !== auth()->id()
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);
        
        $vehicle->load('images');
        
        return Inertia::render('Vehicles/Edit', [
            'vehicle' => $vehicle
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);
        
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1950|max:' . (date('Y') + 1),
            'color' => 'required|string|max:255',
            'license_plate' => 'required|string|max:255|unique:vehicles,license_plate,' . $vehicle->id,
            'mileage' => 'required|integer|min:0',
            'fuel_type' => 'required|in:gasoline,diesel,electric,hybrid',
            'transmission' => 'required|in:manual,automatic',
            'seats' => 'required|integer|min:1|max:9',
            'doors' => 'required|integer|min:2|max:5',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'daily_rate' => 'required|numeric|min:1',
            'weekly_rate' => 'nullable|numeric|min:1',
            'monthly_rate' => 'nullable|numeric|min:1',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'status' => 'required|in:active,inactive,maintenance',
            'is_available' => 'boolean'
        ]);

        $vehicle->update($validated);

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule mis à jour avec succès !');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);
        
        // Delete associated images from storage
        foreach ($vehicle->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        
        $vehicle->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Véhicule supprimé avec succès !');
    }

    public function myVehicles()
    {
        $vehicles = Vehicle::where('owner_id', auth()->id())
            ->with(['images', 'rentals'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Vehicles/MyVehicles', [
            'vehicles' => $vehicles
        ]);
    }
}
