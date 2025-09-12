<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Recommendation extends Model
{
    protected $fillable = [
        'user_id',
        'recommendation_type',
        'entity_type',
        'entity_id',
        'score',
        'reason',
        'factors',
        'is_viewed',
        'is_clicked',
        'is_converted',
        'viewed_at',
        'clicked_at',
        'converted_at',
        'expires_at',
    ];

    protected $casts = [
        'factors' => 'array',
        'score' => 'float',
        'is_viewed' => 'boolean',
        'is_clicked' => 'boolean',
        'is_converted' => 'boolean',
        'viewed_at' => 'datetime',
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(RecommendationFeedback::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('recommendation_type', $type);
    }

    public function scopeTopScored($query, $limit = 10)
    {
        return $query->orderByDesc('score')->limit($limit);
    }

    public function scopeUnviewed($query)
    {
        return $query->where('is_viewed', false);
    }

    public function scopeConversions($query)
    {
        return $query->where('is_converted', true);
    }

    // Helpers
    public function getEntity()
    {
        switch ($this->entity_type) {
            case 'vehicle':
                return Vehicle::with(['owner', 'images'])->find($this->entity_id);
            case 'property':
                return Property::with(['owner', 'images'])->find($this->entity_id);
            case 'equipment':
                return Equipment::with(['owner', 'images'])->find($this->entity_id);
            default:
                return null;
        }
    }

    public function markAsViewed(): void
    {
        if (!$this->is_viewed) {
            $this->update([
                'is_viewed' => true,
                'viewed_at' => now(),
            ]);
        }
    }

    public function markAsClicked(): void
    {
        if (!$this->is_clicked) {
            $this->update([
                'is_clicked' => true,
                'clicked_at' => now(),
            ]);
            
            // Also mark as viewed if not already
            $this->markAsViewed();
        }
    }

    public function markAsConverted(): void
    {
        if (!$this->is_converted) {
            $this->update([
                'is_converted' => true,
                'converted_at' => now(),
            ]);
            
            // Also mark as clicked and viewed
            $this->markAsClicked();
        }
    }

    public function getEffectiveness(): float
    {
        if ($this->is_converted) {
            return 1.0;
        } elseif ($this->is_clicked) {
            return 0.5;
        } elseif ($this->is_viewed) {
            return 0.2;
        }
        
        return 0.0;
    }

    public function getRecommendationIcon(): string
    {
        return match($this->recommendation_type) {
            'similar' => '🔄',
            'trending' => '🔥',
            'personalized' => '⭐',
            'location_based' => '📍',
            'price_based' => '💰',
            default => '💡',
        };
    }

    public function getRecommendationLabel(): string
    {
        return match($this->recommendation_type) {
            'similar' => 'Similaire',
            'trending' => 'Tendance',
            'personalized' => 'Pour vous',
            'location_based' => 'À proximité',
            'price_based' => 'Dans votre budget',
            default => 'Recommandé',
        };
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getDaysUntilExpiry(): int
    {
        if (!$this->expires_at) {
            return PHP_INT_MAX;
        }
        
        return max(0, now()->diffInDays($this->expires_at, false));
    }
}