<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::active()
            ->withBasicRelations()
            ->withReviewStats();

        // Basic Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('brand', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('fuel_type')) {
            $query->whereIn('fuel_type', (array)$request->fuel_type);
        }

        if ($request->filled('transmission')) {
            $query->whereIn('transmission', (array)$request->transmission);
        }

        // Range Filters
        if ($request->filled('min_seats')) {
            $query->where('seats', '>=', $request->min_seats);
        }

        if ($request->filled('max_seats')) {
            $query->where('seats', '<=', $request->max_seats);
        }

        if ($request->filled('min_price')) {
            $query->where('daily_rate', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('daily_rate', '<=', $request->max_price);
        }

        if ($request->filled('min_year')) {
            $query->where('year', '>=', $request->min_year);
        }

        if ($request->filled('max_year')) {
            $query->where('year', '<=', $request->max_year);
        }

        // Advanced Filters
        if ($request->filled('min_rating')) {
            $query->having('reviews_avg_rating', '>=', $request->min_rating);
        }

        if ($request->filled('features')) {
            $features = (array)$request->features;
            foreach ($features as $feature) {
                $query->whereJsonContains('features', $feature);
            }
        }

        if ($request->filled('doors')) {
            $query->whereIn('doors', (array)$request->doors);
        }

        if ($request->filled('color')) {
            $query->whereIn('color', (array)$request->color);
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        if ($request->filled('max_mileage')) {
            $query->where('mileage', '<=', $request->max_mileage);
        }

        if ($request->filled('has_insurance')) {
            $query->where('has_insurance', true);
        }

        if ($request->filled('instant_booking')) {
            $query->where('instant_booking', true);
        }

        if ($request->filled('min_rental_days')) {
            $query->where('min_rental_days', '<=', $request->min_rental_days);
        }

        // Location-based search (distance from coordinates)
        if ($request->filled('latitude') && $request->filled('longitude') && $request->filled('radius')) {
            $query->nearLocation($request->latitude, $request->longitude, $request->radius);
        }

        // Check availability for specific dates
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->availableForDates($request->start_date, $request->end_date);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('daily_rate', 'asc');
                break;
            case 'price_high':
                $query->orderBy('daily_rate', 'desc');
                break;
            case 'rating':
                $query->orderBy('reviews_avg_rating', 'desc');
                break;
            case 'reviews':
                $query->orderBy('reviews_count', 'desc');
                break;
            case 'year':
                $query->orderBy('year', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        $vehicles = $query->paginate(12)->withQueryString();

        // Get filter options for UI with caching
        $filterOptions = Cache::remember('vehicle_filter_options', 3600, function () {
            return [
                'brands' => Vehicle::where('status', 'active')->distinct()->pluck('brand'),
                'cities' => Vehicle::where('status', 'active')->distinct()->pluck('city'),
                'colors' => Vehicle::where('status', 'active')->distinct()->pluck('color'),
                'vehicle_types' => Vehicle::where('status', 'active')->whereNotNull('vehicle_type')->distinct()->pluck('vehicle_type'),
                'features' => ['GPS', 'Climatisation', 'Bluetooth', 'Caméra de recul', 'Régulateur de vitesse', 'Sièges chauffants', 'Toit ouvrant', 'Parking assisté'],
                'fuel_types' => ['gasoline', 'diesel', 'electric', 'hybrid'],
                'transmissions' => ['manual', 'automatic'],
            ];
        });

        // Get user's favorited vehicle IDs if authenticated
        $favoritedVehicleIds = [];
        if (auth()->check()) {
            $favoritedVehicleIds = auth()->user()->favorites()
                ->pluck('vehicle_id')
                ->toArray();
        }

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->all(),
            'filterOptions' => $filterOptions,
            'favoritedVehicleIds' => $favoritedVehicleIds
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

        // Clear cache when new vehicle is added
        Cache::forget('vehicle_filter_options');

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule ajouté avec succès !');
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['owner', 'images', 'reviews.reviewer']);

        // Get vehicle bookings for the calendar
        $bookings = $vehicle->rentals()
            ->whereIn('status', ['confirmed', 'active'])
            ->where('end_date', '>=', now())
            ->select('start_date', 'end_date', 'status')
            ->get();

        // Check if vehicle is favorited by current user
        $isFavorited = false;
        if (auth()->check()) {
            $isFavorited = auth()->user()->hasFavorited($vehicle);
        }

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'bookings' => $bookings,
            'canRent' => auth()->check() && auth()->user()->canRent() && $vehicle->owner_id !== auth()->id(),
            'isFavorited' => $isFavorited
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        Gate::authorize('update', $vehicle);

        $vehicle->load('images');

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => $vehicle
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        Gate::authorize('update', $vehicle);

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

        // Clear cache when vehicle is updated
        Cache::forget('vehicle_filter_options');

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

        // Clear cache when vehicle is deleted
        Cache::forget('vehicle_filter_options');

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
