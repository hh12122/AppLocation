<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationFeedback extends Model
{
    protected $table = 'recommendation_feedback';
    
    protected $fillable = [
        'user_id',
        'recommendation_id',
        'feedback_type',
        'comment',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class);
    }

    // Scopes
    public function scopePositive($query)
    {
        return $query->where('feedback_type', 'helpful');
    }

    public function scopeNegative($query)
    {
        return $query->whereIn('feedback_type', ['not_helpful', 'irrelevant']);
    }

    // Helpers
    public function isPositive(): bool
    {
        return $this->feedback_type === 'helpful';
    }

    public function isNegative(): bool
    {
        return in_array($this->feedback_type, ['not_helpful', 'irrelevant']);
    }

    public function getFeedbackIcon(): string
    {
        return match($this->feedback_type) {
            'helpful' => '👍',
            'not_helpful' => '👎',
            'irrelevant' => '🚫',
            'already_seen' => '👀',
            default => '📝',
        };
    }

    public function getFeedbackLabel(): string
    {
        return match($this->feedback_type) {
            'helpful' => 'Utile',
            'not_helpful' => 'Pas utile',
            'irrelevant' => 'Non pertinent',
            'already_seen' => 'Déjà vu',
            default => 'Feedback',
        };
    }
}