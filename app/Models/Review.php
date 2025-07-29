<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'rental_id',
        'reviewer_id',
        'reviewee_id',
        'type',
        'rating',
        'comment',
        'criteria_ratings',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'criteria_ratings' => 'array',
            'is_public' => 'boolean',
        ];
    }

    // Relations
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Helpers
    public function isVehicleReview(): bool
    {
        return $this->type === 'vehicle';
    }

    public function isOwnerReview(): bool
    {
        return $this->type === 'owner';
    }

    public function isRenterReview(): bool
    {
        return $this->type === 'renter';
    }

    public function getStarsDisplay(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }
}
