<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'rental_id',
        'reviewer_id',
        'reviewee_id',
        'reviewable_id',
        'reviewable_type',
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

    // Polymorphic relation
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    // Other relations
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

    // Helpers
    public function getStarsDisplay(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }
}
