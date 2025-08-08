<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\Rental;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Review $review): bool
    {
        return $review->is_public || 
               $user->id === $review->reviewer_id || 
               $user->id === $review->reviewee_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function review(User $user, Rental $rental): bool
    {
        if ($rental->status !== 'completed') {
            return false;
        }

        if ($user->id === $rental->renter_id || $user->id === $rental->vehicle->owner_id) {
            $existingReview = Review::where('rental_id', $rental->id)
                ->where('reviewer_id', $user->id)
                ->exists();
            
            return !$existingReview;
        }

        return false;
    }

    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->reviewer_id && 
               $review->created_at->diffInDays(now()) <= 7;
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->reviewer_id && 
               $review->created_at->diffInDays(now()) <= 30;
    }
}