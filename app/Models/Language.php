<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'is_active',
        'is_default',
        'is_rtl',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_rtl' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeRtl($query)
    {
        return $query->where('is_rtl', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helpers
    public function getDisplayName(): string
    {
        return $this->native_name . ' (' . $this->name . ')';
    }

    public function getFlagEmoji(): string
    {
        return $this->flag ?: '🌐';
    }

    public function makeDefault(): void
    {
        // Remove default from all other languages
        static::where('is_default', true)->update(['is_default' => false]);
        
        // Set this language as default
        $this->update(['is_default' => true]);
    }

    public function getLocaleConfig(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'native_name' => $this->native_name,
            'flag' => $this->flag,
            'is_rtl' => $this->is_rtl,
            'direction' => $this->is_rtl ? 'rtl' : 'ltr',
        ];
    }
}