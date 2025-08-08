<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'notes',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithVehicleDetails($query)
    {
        return $query->with([
            'vehicle' => function ($q) {
                $q->with(['owner:id,name,rating', 'images' => function ($img) {
                    $img->where('is_primary', true)->orWhere(function($query) {
                        $query->orderBy('sort_order')->limit(1);
                    });
                }])->withCount('reviews')->withAvg('reviews', 'rating');
            }
        ]);
    }
}
