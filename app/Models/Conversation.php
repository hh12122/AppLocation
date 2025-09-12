<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'renter_id',
        'owner_id',
        'last_message_at',
        'is_archived'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_archived' => 'boolean'
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    public function unreadMessagesFor(User $user): HasMany
    {
        return $this->hasMany(Message::class)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at');
    }

    public function getUnreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function getOtherParticipant(User $user): User
    {
        return $user->id === $this->renter_id ? $this->owner : $this->renter;
    }

    public function isParticipant(User $user): bool
    {
        return $user->id === $this->renter_id || $user->id === $this->owner_id;
    }

    public function markAllMessagesAsReadFor(User $user): void
    {
        $this->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function findOrCreateForRental(Rental $rental): self
    {
        return static::firstOrCreate([
            'rental_id' => $rental->id,
        ], [
            'renter_id' => $rental->renter_id,
            'owner_id' => $rental->vehicle->owner_id,
        ]);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('renter_id', $user->id)
              ->orWhere('owner_id', $user->id);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}