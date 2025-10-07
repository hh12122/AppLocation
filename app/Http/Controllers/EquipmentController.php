<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentImage;
use App\Models\EquipmentBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class EquipmentController extends Controller
{
    /**
     * Display a listing of equipment.
     */
    public function index(Request $request)
    {
        $query = Equipment::query()->active()->with(['owner', 'primaryImage']);

        // Apply filters
        $this->applyFilters($query, $request);

        $equipment = $query->orderBy('is_featured', 'desc')
                          ->orderBy('is_premium', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(12)
                          ->appends($request->query());

        return Inertia::render('Equipment/Index', [
            'equipment' => $equipment,
            'categories' => Equipment::getCategoryConfig(),
            'filters' => $this->getFilterOptions(),
            'searchParams' => $request->only([
                'search', 'category', 'subcategory', 'city', 'min_price', 'max_price',
                'features', 'rental_unit', 'delivery_available', 'instant_booking',
                'start_date', 'end_date'
            ]),
        ]);
    }

    /**
     * Display equipment by category.
     */
    public function category(string $category, Request $request)
    {
        if (!array_key_exists($category, Equipment::getCategoryConfig())) {
            abort(404);
        }

        $query = Equipment::query()
            ->active()
            ->ofCategory($category)
            ->with(['owner', 'primaryImage']);

        // Apply filters
        $this->applyFilters($query, $request);

        $equipment = $query->orderBy('is_featured', 'desc')
                          ->orderBy('rating', 'desc')
                          ->paginate(12)
                          ->appends($request->query());

        $categoryConfig = Equipment::getCategoryConfig()[$category];

        return Inertia::render('Equipment/Category', [
            'equipment' => $equipment,
            'category' => $category,
            'categoryConfig' => $categoryConfig,
            'filters' => $this->getFilterOptions($category),
            'searchParams' => $request->only([
                'search', 'subcategory', 'city', 'min_price', 'max_price',
                'features', 'rental_unit', 'delivery_available', 'instant_booking',
                'start_date', 'end_date'
            ]),
        ]);
    }

    /**
     * Show the equipment creation form.
     */
    public function create(Request $request)
    {
        if (!Auth::user()?->canListEquipment()) {
            return to_route('equipment.index')
                ->with('error', 'Vous devez avoir un profil vérifié pour lister du matériel.');
        }

        $category = $request->get('category', 'sports_equipment');

        return Inertia::render('Equipment/Create', [
            'categories' => Equipment::getCategoryConfig(),
            'selectedCategory' => $category,
        ]);
    }

    /**
     * Store a new equipment.
     */
    public function store(Request $request)
    {
        if (!Auth::user()?->canListEquipment()) {
            abort(403, 'Unauthorized to create equipment');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:sports_equipment,tools_material,boats,spaces',
            'subcategory' => 'required|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1900|max:2030',
            'condition' => 'required|in:new,excellent,good,fair,poor',
            'length' => 'nullable|numeric|min:0|max:1000',
            'width' => 'nullable|numeric|min:0|max:1000',
            'height' => 'nullable|numeric|min:0|max:1000',
            'weight' => 'nullable|numeric|min:0|max:10000',
            'size' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:1|max:1000',
            'area_sqm' => 'nullable|numeric|min:1|max:10000',
            'features' => 'nullable|array',
            'included_items' => 'nullable|array',
            'safety_equipment' => 'nullable|array',
            'usage_instructions' => 'nullable|string|max:2000',
            'safety_instructions' => 'nullable|string|max:2000',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'pickup_instructions' => 'nullable|string|max:1000',
            'delivery_available' => 'boolean',
            'delivery_radius' => 'nullable|numeric|min:1|max:100',
            'delivery_fee' => 'nullable|numeric|min:0|max:500',
            'hourly_rate' => 'nullable|numeric|min:1|max:1000',
            'daily_rate' => 'nullable|numeric|min:1|max:5000',
            'weekly_rate' => 'nullable|numeric|min:10|max:20000',
            'monthly_rate' => 'nullable|numeric|min:50|max:50000',
            'security_deposit' => 'nullable|numeric|min:0|max:10000',
            'cleaning_fee' => 'nullable|numeric|min:0|max:500',
            'min_rental_duration' => 'required|integer|min:1|max:365',
            'max_rental_duration' => 'nullable|integer|min:1|max:365',
            'rental_unit' => 'required|in:hour,day,week,month',
            'pickup_type' => 'required|in:pickup_only,delivery_only,both',
            'min_age' => 'nullable|integer|min:16|max:99',
            'license_required' => 'boolean',
            'license_type' => 'nullable|string|max:100',
            'experience_required' => 'boolean',
            'rental_requirements' => 'nullable|string|max:1000',
            'restrictions' => 'nullable|array',
            'insurance_included' => 'boolean',
            'insurance_details' => 'nullable|string|max:1000',
            'liability_terms' => 'nullable|string|max:1000',
            'instant_booking' => 'boolean',
            'images' => 'required|array|min:1|max:15',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            // Category-specific attributes
            'category_attributes' => 'nullable|array',
        ]);

        $validated['owner_id'] = Auth::id();

        // Store category-specific attributes in the correct JSON field
        $categoryAttributes = $validated['category_attributes'] ?? [];
        unset($validated['category_attributes']);

        $validated[$validated['category'] . '_attributes'] = $categoryAttributes;

        $equipment = Equipment::create($validated);

        // Handle image uploads
        $this->handleImageUploads($equipment, $request->file('images'));

        return redirect()->route('equipment.show', $equipment)
            ->with('success', 'Matériel créé avec succès !');
    }

    /**
     * Show a specific equipment.
     */
    public function show(Equipment $equipment)
    {
        $equipment->load([
            'owner',
            'images' => function ($query) {
                $query->orderBy('sort_order');
            },
            'reviews.reviewer',
        ]);

        // Increment view count
        $equipment->incrementViewCount();

        // Get similar equipment
        $similarEquipment = Equipment::active()
            ->where('id', '!=', $equipment->id)
            ->where('category', $equipment->category)
            ->inCity($equipment->city)
            ->with(['primaryImage'])
            ->limit(4)
            ->get();

        return Inertia::render('Equipment/Show', [
            'equipment' => $equipment,
            'categoryConfig' => Equipment::getCategoryConfig()[$equipment->category],
            'similarEquipment' => $similarEquipment,
            'isFavorite' => Auth::check() ?
                \App\Models\Favorite::where('user_id', Auth::id())
                    ->where('favoritable_type', Equipment::class)
                    ->where('favoritable_id', $equipment->id)
                    ->exists() : false,
        ]);
    }

    /**
     * Show the equipment edit form.
     */
    public function edit(Equipment $equipment)
    {
        if ($equipment->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to edit this equipment');
        }

        $equipment->load(['images' => function ($query) {
            $query->orderBy('sort_order');
        }]);

        return Inertia::render('Equipment/Edit', [
            'equipment' => $equipment,
            'categories' => Equipment::getCategoryConfig(),
        ]);
    }

    /**
     * Update equipment.
     */
    public function update(Request $request, Equipment $equipment)
    {
        if ($equipment->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to update this equipment');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'subcategory' => 'required|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1900|max:2030',
            'condition' => 'required|in:new,excellent,good,fair,poor',
            'length' => 'nullable|numeric|min:0|max:1000',
            'width' => 'nullable|numeric|min:0|max:1000',
            'height' => 'nullable|numeric|min:0|max:1000',
            'weight' => 'nullable|numeric|min:0|max:10000',
            'size' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:1|max:1000',
            'area_sqm' => 'nullable|numeric|min:1|max:10000',
            'features' => 'nullable|array',
            'included_items' => 'nullable|array',
            'safety_equipment' => 'nullable|array',
            'usage_instructions' => 'nullable|string|max:2000',
            'safety_instructions' => 'nullable|string|max:2000',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'pickup_instructions' => 'nullable|string|max:1000',
            'delivery_available' => 'boolean',
            'delivery_radius' => 'nullable|numeric|min:1|max:100',
            'delivery_fee' => 'nullable|numeric|min:0|max:500',
            'hourly_rate' => 'nullable|numeric|min:1|max:1000',
            'daily_rate' => 'nullable|numeric|min:1|max:5000',
            'weekly_rate' => 'nullable|numeric|min:10|max:20000',
            'monthly_rate' => 'nullable|numeric|min:50|max:50000',
            'security_deposit' => 'nullable|numeric|min:0|max:10000',
            'cleaning_fee' => 'nullable|numeric|min:0|max:500',
            'min_rental_duration' => 'required|integer|min:1|max:365',
            'max_rental_duration' => 'nullable|integer|min:1|max:365',
            'rental_unit' => 'required|in:hour,day,week,month',
            'pickup_type' => 'required|in:pickup_only,delivery_only,both',
            'min_age' => 'nullable|integer|min:16|max:99',
            'license_required' => 'boolean',
            'license_type' => 'nullable|string|max:100',
            'experience_required' => 'boolean',
            'rental_requirements' => 'nullable|string|max:1000',
            'restrictions' => 'nullable|array',
            'insurance_included' => 'boolean',
            'insurance_details' => 'nullable|string|max:1000',
            'liability_terms' => 'nullable|string|max:1000',
            'instant_booking' => 'boolean',
            'status' => 'required|in:active,inactive,maintenance',
            'new_images' => 'nullable|array|max:15',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'deleted_images' => 'nullable|array',
            'deleted_images.*' => 'integer|exists:equipment_images,id',
            'category_attributes' => 'nullable|array',
        ]);

        // Update category-specific attributes
        if (isset($validated['category_attributes'])) {
            $categoryAttributes = $validated['category_attributes'];
            unset($validated['category_attributes']);
            $validated[$equipment->category . '_attributes'] = $categoryAttributes;
        }

        $equipment->update($validated);

        // Handle image deletions
        if ($request->has('deleted_images')) {
            EquipmentImage::whereIn('id', $request->deleted_images)
                ->where('equipment_id', $equipment->id)
                ->delete();
        }

        // Handle new image uploads
        if ($request->hasFile('new_images')) {
            $this->handleImageUploads($equipment, $request->file('new_images'));
        }

        return redirect()->route('equipment.show', $equipment)
            ->with('success', 'Matériel mis à jour avec succès !');
    }

    /**
     * Delete equipment.
     */
    public function destroy(Equipment $equipment)
    {
        if ($equipment->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized to delete this equipment');
        }

        // Check for active bookings
        if ($equipment->activeBookings()->exists()) {
            return redirect()->route('equipment.show', $equipment)
                ->with('error', 'Impossible de supprimer du matériel avec des réservations actives.');
        }

        $equipment->delete();

        return redirect()->route('equipment.index')
            ->with('success', 'Matériel supprimé avec succès !');
    }

    /**
     * Show user's equipment.
     */
    public function myEquipment(Request $request)
    {
        $user = Auth::user();

        $equipment = $user->equipment()
            ->with(['primaryImage'])
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                      ->orWhere('city', 'like', "%{$request->search}%");
            })
            ->when($request->category, function ($query) use ($request) {
                $query->where('category', $request->category);
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->appends($request->query());

        return Inertia::render('Equipment/MyEquipment', [
            'equipment' => $equipment,
            'categories' => Equipment::getCategoryConfig(),
            'stats' => [
                'total' => $user->equipment()->count(),
                'active' => $user->equipment()->active()->count(),
                'bookings' => $user->getTotalEquipmentBookings(),
                'earnings' => $user->getEquipmentEarnings(),
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
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhere('brand', 'like', "%{$request->search}%")
                  ->orWhere('city', 'like', "%{$request->search}%");
            });
        }

        // Category
        if ($request->category) {
            $query->ofCategory($request->category);
        }

        // Subcategory
        if ($request->subcategory) {
            $query->ofSubcategory($request->subcategory);
        }

        // City
        if ($request->city) {
            $query->inCity($request->city);
        }

        // Price range
        $rentalUnit = $request->rental_unit ?: 'day';
        if ($request->min_price || $request->max_price) {
            $query->priceRange($rentalUnit, $request->min_price, $request->max_price);
        }

        // Features
        if ($request->features && is_array($request->features)) {
            $query->withFeatures($request->features);
        }

        // Delivery available
        if ($request->delivery_available) {
            $query->withDelivery();
        }

        // No license required
        if ($request->no_license) {
            $query->noLicenseRequired();
        }

        // Instant booking
        if ($request->instant_booking) {
            $query->instantBooking();
        }

        // Date availability
        if ($request->start_date && $request->end_date) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $query->whereDoesntHave('bookings', function ($q) use ($startDate, $endDate) {
                $q->whereIn('status', ['confirmed', 'preparing', 'ready', 'delivered', 'in_use'])
                  ->where('start_datetime', '<', $endDate)
                  ->where('end_datetime', '>', $startDate);
            });
        }

        // Featured equipment first
        if ($request->featured) {
            $query->featured();
        }

        // Premium equipment
        if ($request->premium) {
            $query->premium();
        }
    }

    /**
     * Get filter options for the search form.
     */
    private function getFilterOptions(string $category = null): array
    {
        $baseOptions = [
            'rental_units' => [
                'hour' => 'À l\'heure',
                'day' => 'À la journée',
                'week' => 'À la semaine',
                'month' => 'Au mois',
            ],
            'conditions' => [
                'new' => 'Neuf',
                'excellent' => 'Excellent',
                'good' => 'Bon',
                'fair' => 'Correct',
                'poor' => 'Mauvais',
            ],
            'cities' => Equipment::active()
                ->select('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->toArray(),
        ];

        if ($category) {
            $categoryConfig = Equipment::getCategoryConfig()[$category];
            $baseOptions['subcategories'] = $categoryConfig['subcategories'];
            $baseOptions['common_attributes'] = $categoryConfig['common_attributes'] ?? [];
        } else {
            $baseOptions['categories'] = Equipment::getCategoryConfig();
        }

        return $baseOptions;
    }

    /**
     * Handle image uploads for equipment.
     */
    private function handleImageUploads(Equipment $equipment, array $images): void
    {
        $sortOrder = $equipment->images()->count();

        foreach ($images as $image) {
            $path = $image->store('equipment/' . $equipment->id, 'public');

            EquipmentImage::create([
                'equipment_id' => $equipment->id,
                'image_path' => $path,
                'alt_text' => $equipment->name . ' - Image ' . ($sortOrder + 1),
                'sort_order' => $sortOrder,
                'is_primary' => $sortOrder === 0,
            ]);

            $sortOrder++;
        }
    }
}
