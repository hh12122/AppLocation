<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    protected $table = 'search_histories';
    
    protected $fillable = [
        'user_id',
        'search_query',
        'search_type',
        'filters',
        'results_count',
        'has_interaction',
        'selected_item_type',
        'selected_item_id',
        'session_id',
    ];

    protected $casts = [
        'filters' => 'array',
        'results_count' => 'integer',
        'has_interaction' => 'boolean',
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
        return $query->where('search_type', $type);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('has_interaction', true);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->select('search_query', \DB::raw('COUNT(*) as search_count'))
                    ->groupBy('search_query')
                    ->orderByDesc('search_count')
                    ->limit($limit);
    }

    // Helpers
    public function markAsSuccessful($itemType = null, $itemId = null): void
    {
        $this->update([
            'has_interaction' => true,
            'selected_item_type' => $itemType,
            'selected_item_id' => $itemId,
        ]);
    }

    public function getSelectedItem()
    {
        if (!$this->selected_item_type || !$this->selected_item_id) {
            return null;
        }

        switch ($this->selected_item_type) {
            case 'vehicle':
                return Vehicle::find($this->selected_item_id);
            case 'property':
                return Property::find($this->selected_item_id);
            case 'equipment':
                return Equipment::find($this->selected_item_id);
            default:
                return null;
        }
    }

    public function getSuccessRate(): float
    {
        if ($this->results_count === 0) {
            return 0;
        }
        
        return $this->has_interaction ? 1.0 : 0.0;
    }

    public function getSimilarSearches($limit = 5): \Illuminate\Support\Collection
    {
        return static::where('search_query', 'LIKE', '%' . $this->search_query . '%')
                    ->where('id', '!=', $this->id)
                    ->where('has_interaction', true)
                    ->select('search_query')
                    ->distinct()
                    ->limit($limit)
                    ->pluck('search_query');
    }
}