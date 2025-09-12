<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrendingItem extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'trend_type',
        'location',
        'view_count',
        'click_count',
        'booking_count',
        'favorite_count',
        'trend_score',
        'period_date',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'click_count' => 'integer',
        'booking_count' => 'integer',
        'favorite_count' => 'integer',
        'trend_score' => 'float',
        'period_date' => 'date',
    ];

    // Scopes
    public function scopeOfType($query, $type)
    {
        return $query->where('entity_type', $type);
    }

    public function scopeTrending($query, $type = 'daily')
    {
        return $query->where('trend_type', $type);
    }

    public function scopeInLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeForPeriod($query, $date)
    {
        return $query->where('period_date', $date);
    }

    public function scopeTopTrending($query, $limit = 10)
    {
        return $query->orderByDesc('trend_score')->limit($limit);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('period_date', '>=', now()->subDays($days));
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

    public function getTrendIcon(): string
    {
        if ($this->trend_score >= 0.8) {
            return '🔥🔥🔥';
        } elseif ($this->trend_score >= 0.6) {
            return '🔥🔥';
        } elseif ($this->trend_score >= 0.4) {
            return '🔥';
        } else {
            return '📈';
        }
    }

    public function getTrendLabel(): string
    {
        if ($this->trend_score >= 0.8) {
            return 'Très populaire';
        } elseif ($this->trend_score >= 0.6) {
            return 'Populaire';
        } elseif ($this->trend_score >= 0.4) {
            return 'En hausse';
        } else {
            return 'Tendance';
        }
    }

    public function getEngagementRate(): float
    {
        if ($this->view_count === 0) {
            return 0;
        }
        
        $engagements = $this->click_count + ($this->favorite_count * 2) + ($this->booking_count * 3);
        return min(1.0, $engagements / $this->view_count);
    }

    public function getConversionRate(): float
    {
        if ($this->click_count === 0) {
            return 0;
        }
        
        return $this->booking_count / $this->click_count;
    }
}