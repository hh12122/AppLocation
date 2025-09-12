<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties.
     */
    public function index(Request $request)
    {
        $query = Property::query()->active()->with(['owner', 'primaryImage']);

        // Apply filters
        $this->applyFilters($query, $request);

        $properties = $query->orderBy('is_featured', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(12)
                          ->appends($request->query());

        return Inertia::render('Properties/Index', [
            'properties' => $properties,
            'filters' => $this->getFilterOptions(),
            'searchParams' => $request->only([
                'search', 'city', 'property_type', 'room_type', 'min_guests',
                'min_price', 'max_price', 'amenities', 'checkin', 'checkout'
            ]),
        ]);
    }

    /**
     * Show the property creation form.
     */
    public function create()
    {
        if (!Auth::user()->canListProperties()) {
            return redirect()->route('properties.index')
                ->with('error', 'Vous devez avoir un permis vérifié pour lister une propriété.');
        }

        return Inertia::render('Properties/Create');
    }

    /**
     * Store a new property.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->canListProperties()) {
            abort(403, 'Unauthorized to create properties');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'property_type' => 'required|in:apartment,house,studio,villa,loft,townhouse,cottage,chalet,castle,other',
            'room_type' => 'required|in:entire_place,private_room,shared_room',
            'bedrooms' => 'required|integer|min:0|max:20',
            'bathrooms' => 'required|integer|min:1|max:20',
            'beds' => 'required|integer|min:1|max:50',
            'max_guests' => 'required|integer|min:1|max:50',
            'area_sqm' => 'nullable|numeric|min:10|max:2000',
            'floor_level' => 'nullable|integer|min:-5|max:200',
            'has_elevator' => 'boolean',
            'has_parking' => 'boolean',
            'has_balcony' => 'boolean',
            'has_terrace' => 'boolean',
            'has_garden' => 'boolean',
            'amenities' => 'nullable|array',
            'safety_features' => 'nullable|array',
            'house_rules' => 'nullable|array',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_description' => 'nullable|string|max:1000',
            'nightly_rate' => 'required|numeric|min:10|max:10000',
            'weekly_rate' => 'nullable|numeric|min:50|max:50000',
            'monthly_rate' => 'nullable|numeric|min:200|max:200000',
            'cleaning_fee' => 'nullable|numeric|min:0|max:1000',
            'security_deposit' => 'nullable|numeric|min:0|max:5000',
            'min_nights' => 'required|integer|min:1|max:365',
            'max_nights' => 'nullable|integer|min:1|max:365',
            'instant_booking' => 'boolean',
            'checkin_start' => 'required|date_format:H:i',
            'checkin_end' => 'required|date_format:H:i|after:checkin_start',
            'checkout_time' => 'required|date_format:H:i',
            'checkin_method' => 'required|in:self_checkin,host_greeting,concierge,lockbox,smart_lock',
            'checkin_instructions' => 'nullable|string|max:1000',
            'host_about' => 'nullable|string|max:1000',
            'host_languages' => 'nullable|array',
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max per image
        ]);

        $validated['owner_id'] = Auth::id();

        $property = Property::create($validated);

        // Handle image uploads
        $this->handleImageUploads($property, $request->file('images'));

        return redirect()->route('properties.show', $property)
            ->with('success', 'Propriété créée avec succès !');
    }

    /**
     * Show a specific property.
     */
    public function show(Property $property)
    {
        $property->load([
            'owner',
            'images' => function ($query) {
                $query->orderBy('sort_order');
            },
            'reviews.reviewer',
        ]);

        // Increment view count
        $property->incrementViewCount();

        // Get similar properties
        $similarProperties = Property::active()
            ->where('id', '!=', $property->id)
            ->inCity($property->city)
            ->ofType($property->property_type)
            ->with(['primaryImage'])
            ->limit(4)
            ->get();

        return Inertia::render('Properties/Show', [
            'property' => $property,
            'similarProperties' => $similarProperties,
            'isFavorite' => Auth::check() ? Auth::user()->favoriteVehicles()->where('property_id', $property->id)->exists() : false,
        ]);
    }

    /**
     * Show the property edit form.
     */
    public function edit(Property $property)
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to edit this property');
        }

        $property->load(['images' => function ($query) {
            $query->orderBy('sort_order');
        }]);

        return Inertia::render('Properties/Edit', [
            'property' => $property,
        ]);
    }

    /**
     * Update a property.
     */
    public function update(Request $request, Property $property)
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to update this property');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'property_type' => 'required|in:apartment,house,studio,villa,loft,townhouse,cottage,chalet,castle,other',
            'room_type' => 'required|in:entire_place,private_room,shared_room',
            'bedrooms' => 'required|integer|min:0|max:20',
            'bathrooms' => 'required|integer|min:1|max:20',
            'beds' => 'required|integer|min:1|max:50',
            'max_guests' => 'required|integer|min:1|max:50',
            'area_sqm' => 'nullable|numeric|min:10|max:2000',
            'floor_level' => 'nullable|integer|min:-5|max:200',
            'has_elevator' => 'boolean',
            'has_parking' => 'boolean',
            'has_balcony' => 'boolean',
            'has_terrace' => 'boolean',
            'has_garden' => 'boolean',
            'amenities' => 'nullable|array',
            'safety_features' => 'nullable|array',
            'house_rules' => 'nullable|array',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_description' => 'nullable|string|max:1000',
            'nightly_rate' => 'required|numeric|min:10|max:10000',
            'weekly_rate' => 'nullable|numeric|min:50|max:50000',
            'monthly_rate' => 'nullable|numeric|min:200|max:200000',
            'cleaning_fee' => 'nullable|numeric|min:0|max:1000',
            'security_deposit' => 'nullable|numeric|min:0|max:5000',
            'min_nights' => 'required|integer|min:1|max:365',
            'max_nights' => 'nullable|integer|min:1|max:365',
            'instant_booking' => 'boolean',
            'checkin_start' => 'required|date_format:H:i',
            'checkin_end' => 'required|date_format:H:i|after:checkin_start',
            'checkout_time' => 'required|date_format:H:i',
            'checkin_method' => 'required|in:self_checkin,host_greeting,concierge,lockbox,smart_lock',
            'checkin_instructions' => 'nullable|string|max:1000',
            'host_about' => 'nullable|string|max:1000',
            'host_languages' => 'nullable|array',
            'status' => 'required|in:active,inactive,maintenance',
            'new_images' => 'nullable|array|max:20',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'deleted_images' => 'nullable|array',
            'deleted_images.*' => 'integer|exists:property_images,id',
        ]);

        $property->update($validated);

        // Handle image deletions
        if ($request->has('deleted_images')) {
            PropertyImage::whereIn('id', $request->deleted_images)
                ->where('property_id', $property->id)
                ->delete();
        }

        // Handle new image uploads
        if ($request->hasFile('new_images')) {
            $this->handleImageUploads($property, $request->file('new_images'));
        }

        return redirect()->route('properties.show', $property)
            ->with('success', 'Propriété mise à jour avec succès !');
    }

    /**
     * Delete a property.
     */
    public function destroy(Property $property)
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to delete this property');
        }

        // Check for active bookings
        if ($property->activeBookings()->exists()) {
            return redirect()->route('properties.show', $property)
                ->with('error', 'Impossible de supprimer une propriété avec des réservations actives.');
        }

        $property->delete();

        return redirect()->route('properties.index')
            ->with('success', 'Propriété supprimée avec succès !');
    }

    /**
     * Show user's properties.
     */
    public function myProperties(Request $request)
    {
        $properties = Auth::user()
            ->properties()
            ->with(['primaryImage'])
            ->when($request->search, function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->search}%")
                      ->orWhere('city', 'like', "%{$request->search}%");
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->appends($request->query());

        return Inertia::render('Properties/MyProperties', [
            'properties' => $properties,
            'stats' => [
                'total' => Auth::user()->properties()->count(),
                'active' => Auth::user()->properties()->active()->count(),
                'bookings' => Auth::user()->getTotalPropertyBookings(),
                'earnings' => Auth::user()->getPropertyEarnings(),
            ],
        ]);
    }

    /**
     * Apply search and filter parameters to the query.
     */
    private function applyFilters($query, Request $request): void
    {
        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhere('city', 'like', "%{$request->search}%");
            });
        }

        // City
        if ($request->city) {
            $query->inCity($request->city);
        }

        // Property type
        if ($request->property_type) {
            $query->ofType($request->property_type);
        }

        // Room type
        if ($request->room_type) {
            $query->roomType($request->room_type);
        }

        // Minimum guests
        if ($request->min_guests) {
            $query->minGuests((int)$request->min_guests);
        }

        // Price range
        if ($request->min_price || $request->max_price) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        // Amenities
        if ($request->amenities && is_array($request->amenities)) {
            $query->withAmenities($request->amenities);
        }

        // Date availability
        if ($request->checkin && $request->checkout) {
            $checkin = Carbon::parse($request->checkin);
            $checkout = Carbon::parse($request->checkout);
            
            $query->whereDoesntHave('bookings', function ($q) use ($checkin, $checkout) {
                $q->whereIn('status', ['confirmed', 'checked_in'])
                  ->where('checkin_date', '<', $checkout)
                  ->where('checkout_date', '>', $checkin);
            });
        }

        // Instant booking
        if ($request->instant_booking) {
            $query->instantBooking();
        }

        // Featured properties first
        if ($request->featured) {
            $query->featured();
        }

        // Luxury properties
        if ($request->luxury) {
            $query->luxury();
        }
    }

    /**
     * Get filter options for the search form.
     */
    private function getFilterOptions(): array
    {
        return [
            'property_types' => [
                'apartment' => 'Appartement',
                'house' => 'Maison',
                'studio' => 'Studio',
                'villa' => 'Villa',
                'loft' => 'Loft',
                'townhouse' => 'Maison de ville',
                'cottage' => 'Cottage',
                'chalet' => 'Chalet',
                'castle' => 'Château',
                'other' => 'Autre',
            ],
            'room_types' => [
                'entire_place' => 'Logement entier',
                'private_room' => 'Chambre privée',
                'shared_room' => 'Chambre partagée',
            ],
            'amenities' => [
                'wifi' => 'WiFi',
                'kitchen' => 'Cuisine',
                'washing_machine' => 'Lave-linge',
                'tv' => 'Télévision',
                'air_conditioning' => 'Climatisation',
                'heating' => 'Chauffage',
                'pool' => 'Piscine',
                'hot_tub' => 'Jacuzzi',
                'gym' => 'Salle de sport',
                'workspace' => 'Espace de travail',
                'fireplace' => 'Cheminée',
                'bbq' => 'Barbecue',
                'pets_allowed' => 'Animaux acceptés',
                'smoking_allowed' => 'Fumeurs acceptés',
            ],
            'cities' => Property::active()
                ->select('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->toArray(),
        ];
    }

    /**
     * Handle image uploads for a property.
     */
    private function handleImageUploads(Property $property, array $images): void
    {
        $sortOrder = $property->images()->count();

        foreach ($images as $image) {
            $path = $image->store('properties/' . $property->id, 'public');
            
            PropertyImage::create([
                'property_id' => $property->id,
                'image_path' => $path,
                'alt_text' => $property->title . ' - Image ' . ($sortOrder + 1),
                'sort_order' => $sortOrder,
                'is_primary' => $sortOrder === 0,
            ]);

            $sortOrder++;
        }
    }
}