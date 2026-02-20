<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['reviewer', 'reviewee', 'rental.vehicle'])
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->user_id, fn($q, $userId) =>
                $q->where('reviewer_id', $userId)->orWhere('reviewee_id', $userId)
            )
            ->where('is_public', true)
            ->latest()
            ->paginate(12);

        return Inertia::render('Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['type', 'user_id'])
        ]);
    }

    public function create(Rental $rental)
    {
        Gate::authorize('review', $rental);

        $existingReview = Review::where('rental_id', $rental->id)
            ->where('reviewer_id', auth()->id())
            ->first();

        if ($existingReview) {
            return redirect()->route('rentals.show', $rental)
                ->with('error', 'Vous avez déjà laissé une évaluation pour cette location.');
        }

        $rental->load(['vehicle.owner', 'renter']);

        $reviewType = auth()->id() === $rental->renter_id ? 'vehicle' : 'renter';
        $reviewee = auth()->id() === $rental->renter_id ? $rental->vehicle->owner : $rental->renter;

        return Inertia::render('Reviews/Create', [
            'rental' => $rental,
            'reviewType' => $reviewType,
            'reviewee' => $reviewee
        ]);
    }

    public function store(Request $request, Rental $rental)
    {
        Gate::authorize('review', $rental);

        $existingReview = Review::where('rental_id', $rental->id)
            ->where('reviewer_id', auth()->id())
            ->first();

        if ($existingReview) {
            return redirect()->route('rentals.show', $rental)
                ->with('error', 'Vous avez déjà laissé une évaluation pour cette location.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
            'criteria_ratings' => 'nullable|array',
            'criteria_ratings.cleanliness' => 'nullable|integer|min:1|max:5',
            'criteria_ratings.communication' => 'nullable|integer|min:1|max:5',
            'criteria_ratings.condition' => 'nullable|integer|min:1|max:5',
            'criteria_ratings.value' => 'nullable|integer|min:1|max:5',
            'is_public' => 'boolean'
        ]);

        $rental->load(['vehicle.owner', 'renter']);

        if (auth()->id() === $rental->renter_id) {
            $reviewType = 'vehicle';
            $revieweeId = $rental->vehicle->owner_id;
        } else {
            $reviewType = 'renter';
            $revieweeId = $rental->renter_id;
        }

        DB::transaction(function () use ($validated, $rental, $reviewType, $revieweeId) {
            $review = Review::create([
                'rental_id' => $rental->id,
                'reviewer_id' => auth()->id(),
                'reviewee_id' => $revieweeId,
                'type' => $reviewType,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'criteria_ratings' => $validated['criteria_ratings'] ?? null,
                'is_public' => $validated['is_public'] ?? true
            ]);

            if ($reviewType === 'vehicle') {
                $vehicle = $rental->vehicle;
                $vehicle->rating = $vehicle->reviews()->avg('rating') ?? 0;
                $vehicle->rating_count = $vehicle->reviews()->count();
                $vehicle->save();
            }

            $user = User::find($revieweeId);
            $userReviews = Review::where('reviewee_id', $revieweeId);
            $user->rating = $userReviews->avg('rating') ?? 0;
            $user->rating_count = $userReviews->count();
            $user->save();

            $otherReview = Review::where('rental_id', $rental->id)
                ->where('reviewer_id', '!=', auth()->id())
                ->exists();

            if ($otherReview) {
                $rental->status = 'completed';
                $rental->save();
            }
        });

        return redirect()->route('rentals.show', $rental)
            ->with('success', 'Votre évaluation a été enregistrée avec succès.');
    }

    public function show(Review $review)
    {
        $review->load(['reviewer', 'reviewee', 'rental.vehicle']);

        return Inertia::render('Reviews/Show', [
            'review' => $review
        ]);
    }

    public function edit(Review $review)
    {
        Gate::authorize('update', $review);

        $review->load(['rental.vehicle.owner', 'rental.renter']);

        return Inertia::render('Reviews/Edit', [
            'review' => $review
        ]);
    }

    public function update(Request $request, Review $review)
    {
        Gate::authorize('update', $review);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
            'criteria_ratings' => 'nullable|array',
            'criteria_ratings.cleanliness' => 'nullable|integer|min:1|max:5',
            'criteria_ratings.communication' => 'nullable|integer|min:1|max:5',
            'criteria_ratings.condition' => 'nullable|integer|min:1|max:5',
            'criteria_ratings.value' => 'nullable|integer|min:1|max:5',
            'is_public' => 'boolean'
        ]);

        DB::transaction(function () use ($validated, $review) {
            $review->update($validated);

            if ($review->type === 'vehicle') {
                $vehicle = $review->rental->vehicle;
                $vehicle->rating = $vehicle->reviews()->avg('rating') ?? 0;
                $vehicle->rating_count = $vehicle->reviews()->count();
                $vehicle->save();
            }

            $user = User::find($review->reviewee_id);
            $userReviews = Review::where('reviewee_id', $review->reviewee_id);
            $user->rating = $userReviews->avg('rating') ?? 0;
            $user->rating_count = $userReviews->count();
            $user->save();
        });

        return redirect()->route('reviews.show', $review)
            ->with('success', 'Votre évaluation a été mise à jour.');
    }

    public function destroy(Review $review)
    {
        Gate::authorize('delete', $review);

        DB::transaction(function () use ($review) {
            $revieweeId = $review->reviewee_id;
            $vehicleId = $review->rental->vehicle_id;

            $review->delete();

            if ($review->type === 'vehicle') {
                $vehicle = Vehicle::find($vehicleId);
                if ($vehicle) {
                    $vehicle->rating = $vehicle->reviews()->avg('rating') ?? 0;
                    $vehicle->rating_count = $vehicle->reviews()->count();
                    $vehicle->save();
                }
            }

            $user = User::find($revieweeId);
            if ($user) {
                $userReviews = Review::where('reviewee_id', $revieweeId);
                $user->rating = $userReviews->avg('rating') ?? 0;
                $user->rating_count = $userReviews->count();
                $user->save();
            }
        });

        return redirect()->route('my-rentals')
            ->with('success', 'Votre évaluation a été supprimée.');
    }

    public function vehicleReviews(Vehicle $vehicle)
    {
        $reviews = $vehicle->reviews()
            ->with('reviewer')
            ->where('is_public', true)
            ->latest()
            ->paginate(10);

        return Inertia::render('Reviews/VehicleReviews', [
            'vehicle' => $vehicle->load('owner'),
            'reviews' => $reviews
        ]);
    }

    public function userReviews(User $user)
    {
        $reviews = Review::where('reviewee_id', $user->id)
            ->with(['reviewer', 'rental.vehicle'])
            ->where('is_public', true)
            ->latest()
            ->paginate(10);

        return Inertia::render('Reviews/UserReviews', [
            'user' => $user,
            'reviews' => $reviews
        ]);
    }
}
