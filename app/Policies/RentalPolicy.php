<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rental $rental): bool
    {
        return $user->id === $rental->renter_id || 
               $user->id === $rental->vehicle->owner_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Rental $rental): bool
    {
        return $user->id === $rental->vehicle->owner_id && 
               in_array($rental->status, ['pending', 'confirmed']);
    }

    public function delete(User $user, Rental $rental): bool
    {
        return $user->id === $rental->renter_id && 
               $rental->status === 'pending';
    }

    public function restore(User $user, Rental $rental): bool
    {
        return false;
    }

    public function forceDelete(User $user, Rental $rental): bool
    {
        return false;
    }

    public function cancel(User $user, Rental $rental): bool
    {
        return ($user->id === $rental->renter_id || $user->id === $rental->vehicle->owner_id) &&
               in_array($rental->status, ['pending', 'confirmed']);
    }

    public function confirm(User $user, Rental $rental): bool
    {
        return $user->id === $rental->vehicle->owner_id && 
               $rental->status === 'pending';
    }

    public function pickup(User $user, Rental $rental): bool
    {
        return $user->id === $rental->vehicle->owner_id && 
               $rental->status === 'confirmed';
    }

    public function return(User $user, Rental $rental): bool
    {
        return $user->id === $rental->vehicle->owner_id && 
               $rental->status === 'active';
    }

    public function review(User $user, Rental $rental): bool
    {
        if ($rental->status !== 'completed') {
            return false;
        }

        if ($user->id === $rental->renter_id || $user->id === $rental->vehicle->owner_id) {
            $existingReview = \App\Models\Review::where('rental_id', $rental->id)
                ->where('reviewer_id', $user->id)
                ->exists();
            
            return !$existingReview;
        }

        return false;
    }
}
