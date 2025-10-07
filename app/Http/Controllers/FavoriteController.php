<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Vehicle;
use App\Models\Equipment;
use App\Models\Property;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;

class FavoriteController extends Controller implements HasMiddleware
{
    public function __construct() {}
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth'
        ];
    }
    /**
     * Display the user's wishlist
     */
    public function index(Request $request)
    {
        $query = Favorite::forUser(auth()->id());

        // Filter by type if specified
        $type = $request->get('type', 'all'); // all, vehicles, equipment, properties

        if ($type === 'vehicles') {
            $query->vehicles()->withVehicleDetails();
        } elseif ($type === 'equipment') {
            $query->equipment()->withEquipmentDetails();
        } elseif ($type === 'properties') {
            $query->properties()->withPropertyDetails();
        } else {
            $query->withDetails();
        }

        $favorites = $query->orderBy('created_at', 'desc')->paginate(12);

        return Inertia::render('Favorites/Index', [
            'favorites' => $favorites,
            'currentType' => $type,
            'stats' => [
                'total' => Favorite::forUser(auth()->id())->count(),
                'vehicles' => Favorite::forUser(auth()->id())->vehicles()->count(),
                'equipment' => Favorite::forUser(auth()->id())->equipment()->count(),
                'properties' => Favorite::forUser(auth()->id())->properties()->count(),
            ]
        ]);
    }

    /**
     * Add a vehicle or equipment to favorites
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:vehicle,equipment,property',
            'item_id' => 'required|integer',
            'notes' => 'nullable|string|max:500'
        ]);

        $type = $request->type;
        $itemId = $request->item_id;

        // Get the model class and item
        if ($type === 'vehicle') {
            $request->validate(['item_id' => 'exists:vehicles,id']);
            $item = Vehicle::findOrFail($itemId);
            $modelClass = Vehicle::class;
            $itemName = 'véhicule';
        } elseif ($type === 'equipment') {
            $request->validate(['item_id' => 'exists:equipment,id']);
            $item = Equipment::findOrFail($itemId);
            $modelClass = Equipment::class;
            $itemName = 'matériel';
        } else {
            $request->validate(['item_id' => 'exists:properties,id']);
            $item = Property::findOrFail($itemId);
            $modelClass = Property::class;
            $itemName = 'propriété';
        }

        // Check if user can favorite this item (not their own)
        if ($item->owner_id === auth()->id()) {
            return back()->with('error', "Vous ne pouvez pas ajouter votre propre {$itemName} aux favoris.");
        }

        // Check if already favorited
        $existingFavorite = Favorite::where('user_id', auth()->id())
            ->where('favoritable_type', $modelClass)
            ->where('favoritable_id', $itemId)
            ->first();

        if ($existingFavorite) {
            return back()->with('info', "Ce {$itemName} est déjà dans vos favoris.");
        }

        Favorite::create([
            'user_id' => auth()->id(),
            'favoritable_type' => $modelClass,
            'favoritable_id' => $itemId,
            'vehicle_id' => $type === 'vehicle' ? $itemId : null, // Keep for backwards compatibility
            'notes' => $request->notes
        ]);

        return back()->with('success', ucfirst($itemName) . ' ajouté aux favoris !');
    }

    /**
     * Update favorite notes
     */
    public function update(Request $request, Favorite $favorite)
    {
        // Ensure user owns this favorite
        if ($favorite->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'notes' => 'nullable|string|max:500'
        ]);

        $favorite->update([
            'notes' => $request->notes
        ]);

        return back()->with('success', 'Notes mises à jour !');
    }

    /**
     * Remove an item from favorites (polymorphic)
     */
    public function destroy(Request $request, $itemId)
    {
        $type = $request->get('type', 'vehicle'); // Default to vehicle for backward compatibility

        // Get the model class
        $modelClass = match($type) {
            'equipment' => Equipment::class,
            'property' => Property::class,
            default => Vehicle::class,
        };

        $favorite = Favorite::where('user_id', auth()->id())
            ->where('favoritable_type', $modelClass)
            ->where('favoritable_id', $itemId)
            ->first();

        if (!$favorite) {
            // Try legacy vehicle_id for backward compatibility
            $favorite = Favorite::where('user_id', auth()->id())
                ->where('vehicle_id', $itemId)
                ->first();
        }

        if ($favorite) {
            $favorite->delete();
            $itemName = match($type) {
                'equipment' => 'Matériel',
                'property' => 'Propriété',
                default => 'Véhicule',
            };
            return back()->with('success', $itemName . ' retiré des favoris.');
        }

        return back()->with('error', 'Favori non trouvé.');
    }

    /**
     * Toggle favorite status (add/remove)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'type' => 'required|in:vehicle,equipment,property',
            'item_id' => 'required|integer'
        ]);

        $type = $request->type;
        $itemId = $request->item_id;

        // Get the model class and item
        if ($type === 'vehicle') {
            $request->validate(['item_id' => 'exists:vehicles,id']);
            $item = Vehicle::findOrFail($itemId);
            $modelClass = Vehicle::class;
            $itemName = 'véhicule';
        } elseif ($type === 'equipment') {
            $request->validate(['item_id' => 'exists:equipment,id']);
            $item = Equipment::findOrFail($itemId);
            $modelClass = Equipment::class;
            $itemName = 'matériel';
        } else {
            $request->validate(['item_id' => 'exists:properties,id']);
            $item = Property::findOrFail($itemId);
            $modelClass = Property::class;
            $itemName = 'propriété';
        }

        // Check if user can favorite this item (not their own)
        if ($item->owner_id === auth()->id()) {
            $errorMessage = "Vous ne pouvez pas ajouter votre propre {$itemName} aux favoris.";

            if ($request->wantsJson() || !$request->inertia()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 422);
            }

            // For Inertia requests, return validation error
            throw \Illuminate\Validation\ValidationException::withMessages([
                'owner' => [$errorMessage]
            ]);
        }

        $favorite = Favorite::where('user_id', auth()->id())
            ->where('favoritable_type', $modelClass)
            ->where('favoritable_id', $itemId)
            ->first();

        if ($favorite) {
            // Remove from favorites
            $favorite->delete();
            $message = ucfirst($itemName) . ' retiré des favoris';
            $isFavorited = false;
        } else {
            // Add to favorites
            Favorite::create([
                'user_id' => auth()->id(),
                'favoritable_type' => $modelClass,
                'favoritable_id' => $itemId,
                'vehicle_id' => $type === 'vehicle' ? $itemId : null, // Keep for backwards compatibility
            ]);
            $message = ucfirst($itemName) . ' ajouté aux favoris';
            $isFavorited = true;
        }

        // Return JSON for API calls, back() for Inertia
        if ($request->wantsJson() || !$request->inertia()) {
            return response()->json([
                'success' => true,
                'is_favorited' => $isFavorited,
                'message' => $message
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Check if an item is favorited by the current user
     */
    public function check(Request $request, $itemId)
    {
        if (!auth()->check()) {
            return response()->json(['is_favorited' => false]);
        }

        $type = $request->get('type', 'vehicle'); // Default to vehicle for backwards compatibility

        $modelClass = match($type) {
            'equipment' => Equipment::class,
            'property' => Property::class,
            default => Vehicle::class,
        };

        $isFavorited = Favorite::where('user_id', auth()->id())
            ->where('favoritable_type', $modelClass)
            ->where('favoritable_id', $itemId)
            ->exists();

        return response()->json(['is_favorited' => $isFavorited]);
    }

    /**
     * Legacy method for vehicle favorites check
     */
    public function checkVehicle($vehicleId)
    {
        if (!auth()->check()) {
            return response()->json(['is_favorited' => false]);
        }

        // Check both new polymorphic and old vehicle_id for compatibility
        $isFavorited = Favorite::where('user_id', auth()->id())
            ->where(function ($query) use ($vehicleId) {
                $query->where('vehicle_id', $vehicleId)
                    ->orWhere(function ($subQuery) use ($vehicleId) {
                        $subQuery->where('favoritable_type', Vehicle::class)
                            ->where('favoritable_id', $vehicleId);
                    });
            })
            ->exists();

        return response()->json(['is_favorited' => $isFavorited]);
    }
}
