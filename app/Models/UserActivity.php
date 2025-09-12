<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'activity_type',
        'entity_type',
        'entity_id',
        'metadata',
        'session_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeForEntity($query, $entityType, $entityId = null)
    {
        $query->where('entity_type', $entityType);
        
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        
        return $query;
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helpers
    public function getEntity()
    {
        switch ($this->entity_type) {
            case 'vehicle':
                return Vehicle::find($this->entity_id);
            case 'property':
                return Property::find($this->entity_id);
            case 'equipment':
                return Equipment::find($this->entity_id);
            default:
                return null;
        }
    }

    public function getActivityIcon(): string
    {
        return match($this->activity_type) {
            'view' => '👁️',
            'search' => '🔍',
            'click' => '👆',
            'book' => '📅',
            'favorite' => '❤️',
            'review' => '⭐',
            default => '📌',
        };
    }

    public function getActivityLabel(): string
    {
        return match($this->activity_type) {
            'view' => 'Vu',
            'search' => 'Recherché',
            'click' => 'Cliqué',
            'book' => 'Réservé',
            'favorite' => 'Ajouté aux favoris',
            'review' => 'Évalué',
            default => 'Interagi',
        };
    }
}