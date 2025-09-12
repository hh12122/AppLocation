<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id', // Keep for backwards compatibility
        'favoritable_type',
        'favoritable_id',
        'notes',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }

    // Keep backwards compatibility
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'favoritable_id')
            ->where('favoritable_type', Equipment::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeVehicles($query)
    {
        return $query->where('favoritable_type', Vehicle::class);
    }

    public function scopeEquipment($query)
    {
        return $query->where('favoritable_type', Equipment::class);
    }

    public function scopeWithVehicleDetails($query)
    {
        return $query->with([
            'favoritable' => function ($q) {
                $q->with(['owner:id,name,rating', 'images' => function ($img) {
                    $img->where('is_primary', true)->orWhere(function($query) {
                        $query->orderBy('sort_order')->limit(1);
                    });
                }])->withCount('reviews')->withAvg('reviews', 'rating');
            }
        ])->where('favoritable_type', Vehicle::class);
    }

    public function scopeWithEquipmentDetails($query)
    {
        return $query->with([
            'favoritable' => function ($q) {
                $q->with(['owner:id,name,rating', 'images' => function ($img) {
                    $img->where('is_primary', true)->orWhere(function($query) {
                        $query->orderBy('sort_order')->limit(1);
                    });
                }]);
            }
        ])->where('favoritable_type', Equipment::class);
    }

    public function scopeWithDetails($query)
    {
        return $query->with([
            'favoritable' => function ($q) {
                $q->with(['owner:id,name,rating', 'images' => function ($img) {
                    $img->where('is_primary', true)->orWhere(function($query) {
                        $query->orderBy('sort_order')->limit(1);
                    });
                }]);
            }
        ]);
    }

    // Helper methods
    public function isVehicle(): bool
    {
        return $this->favoritable_type === Vehicle::class;
    }

    public function isEquipment(): bool
    {
        return $this->favoritable_type === Equipment::class;
    }

    public function getFavoritableItem()
    {
        return $this->favoritable ?: $this->vehicle;
    }
}
