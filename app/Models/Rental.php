<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    protected $fillable = [
        'vehicle_id',
        'renter_id',
        'start_date',
        'end_date',
        'status',
        'total_amount',
        'daily_rate',
        'total_days',
        'deposit',
        'special_requests',
        'pickup_datetime',
        'return_datetime',
        'pickup_mileage',
        'return_mileage',
        'pickup_notes',
        'return_notes',
        'pickup_images',
        'return_images',
        'payment_status',
        'payment_method',
        'payment_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'pickup_datetime' => 'datetime',
            'return_datetime' => 'datetime',
            'total_amount' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'deposit' => 'decimal:2',
            'pickup_images' => 'array',
            'return_images' => 'array',
        ];
    }

    // Relations
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Helpers
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canBeReviewed(): bool
    {
        return $this->isCompleted() && $this->return_datetime !== null;
    }

    public function getOwner()
    {
        return $this->vehicle->owner;
    }

    public function isPastDue(): bool
    {
        return $this->end_date < now() && !in_array($this->status, ['completed', 'cancelled']);
    }
}
