<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'preference_type',
        'preference_key',
        'preference_value',
        'weight',
        'confidence',
        'interaction_count',
        'last_interaction_at',
    ];

    protected $casts = [
        'preference_value' => 'array',
        'weight' => 'float',
        'confidence' => 'float',
        'interaction_count' => 'integer',
        'last_interaction_at' => 'datetime',
    ];

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
        return $query->where('preference_type', $type);
    }

    public function scopeHighConfidence($query, $threshold = 0.7)
    {
        return $query->where('confidence', '>=', $threshold);
    }

    public function scopeImportant($query, $threshold = 0.7)
    {
        return $query->where('weight', '>=', $threshold);
    }

    public function scopeActive($query, $days = 30)
    {
        return $query->where('last_interaction_at', '>=', now()->subDays($days));
    }

    // Helpers
    public function incrementInteraction(): void
    {
        $this->increment('interaction_count');
        $this->update(['last_interaction_at' => now()]);
        
        // Increase confidence slightly with each interaction
        $newConfidence = min(1.0, $this->confidence + 0.02);
        $this->update(['confidence' => $newConfidence]);
    }

    public function adjustWeight(float $delta): void
    {
        $newWeight = max(0.0, min(1.0, $this->weight + $delta));
        $this->update(['weight' => $newWeight]);
    }

    public function isStrong(): bool
    {
        return $this->weight >= 0.7 && $this->confidence >= 0.7;
    }

    public function isWeak(): bool
    {
        return $this->weight < 0.3 || $this->confidence < 0.3;
    }

    public function getPreferenceLabel(): string
    {
        return match($this->preference_type) {
            'category' => 'Catégorie préférée',
            'brand' => 'Marque préférée',
            'price_range' => 'Gamme de prix',
            'location' => 'Localisation préférée',
            'features' => 'Caractéristiques recherchées',
            'vehicle_type' => 'Type de véhicule',
            'property_type' => 'Type de propriété',
            'equipment_category' => 'Catégorie d\'équipement',
            default => 'Préférence',
        };
    }

    public function getFormattedValue(): string
    {
        $value = $this->preference_value;
        
        if (is_array($value)) {
            if (isset($value[0]) && isset($value[1]) && count($value) === 2) {
                // Price range
                return $value[0] . '€ - ' . $value[1] . '€';
            }
            return implode(', ', $value);
        }
        
        return (string) $value;
    }
}