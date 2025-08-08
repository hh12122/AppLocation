<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the user's wishlist
     */
    public function index()
    {
        $favorites = Favorite::forUser(auth()->id())
            ->withVehicleDetails()
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return Inertia::render('Favorites/Index', [
            'favorites' => $favorites
        ]);
    }

    /**
     * Add a vehicle to favorites
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'notes' => 'nullable|string|max:500'
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        // Check if user can favorite this vehicle (not their own)
        if ($vehicle->owner_id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas ajouter votre propre véhicule aux favoris.');
        }

        // Check if already favorited
        $existingFavorite = Favorite::where('user_id', auth()->id())
            ->where('vehicle_id', $request->vehicle_id)
            ->first();

        if ($existingFavorite) {
            return back()->with('info', 'Ce véhicule est déjà dans vos favoris.');
        }

        Favorite::create([
            'user_id' => auth()->id(),
            'vehicle_id' => $request->vehicle_id,
            'notes' => $request->notes
        ]);

        return back()->with('success', 'Véhicule ajouté aux favoris !');
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
     * Remove a vehicle from favorites
     */
    public function destroy($vehicleId)
    {
        $favorite = Favorite::where('user_id', auth()->id())
            ->where('vehicle_id', $vehicleId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return back()->with('success', 'Véhicule retiré des favoris.');
        }

        return back()->with('error', 'Favori non trouvé.');
    }

    /**
     * Toggle favorite status (add/remove)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id'
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        // Check if user can favorite this vehicle (not their own)
        if ($vehicle->owner_id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas ajouter votre propre véhicule aux favoris.'
            ], 400);
        }

        $favorite = Favorite::where('user_id', auth()->id())
            ->where('vehicle_id', $request->vehicle_id)
            ->first();

        if ($favorite) {
            // Remove from favorites
            $favorite->delete();
            return response()->json([
                'success' => true,
                'is_favorited' => false,
                'message' => 'Véhicule retiré des favoris'
            ]);
        } else {
            // Add to favorites
            Favorite::create([
                'user_id' => auth()->id(),
                'vehicle_id' => $request->vehicle_id
            ]);
            
            return response()->json([
                'success' => true,
                'is_favorited' => true,
                'message' => 'Véhicule ajouté aux favoris'
            ]);
        }
    }

    /**
     * Check if a vehicle is favorited by the current user
     */
    public function check($vehicleId)
    {
        if (!auth()->check()) {
            return response()->json(['is_favorited' => false]);
        }

        $isFavorited = Favorite::where('user_id', auth()->id())
            ->where('vehicle_id', $vehicleId)
            ->exists();

        return response()->json(['is_favorited' => $isFavorited]);
    }
}
