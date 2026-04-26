<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversable_type',
        'conversable_id',
        'renter_id',
        'owner_id',
        'last_message_at',
        'is_archived',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    public function conversable(): MorphTo
    {
        return $this->morphTo();
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

    public static function findOrCreateFor(Model $conversable): self
    {
        return static::firstOrCreate([
            'conversable_type' => get_class($conversable),
            'conversable_id' => $conversable->id,
        ], [
            'renter_id' => static::resolveRenterId($conversable),
            'owner_id' => static::resolveOwnerId($conversable),
        ]);
    }

    public static function findOrCreateForRental(Rental $rental): self
    {
        return static::findOrCreateFor($rental);
    }

    public static function isUserAuthorizedFor(Model $conversable, User $user): bool
    {
        return $user->id === static::resolveRenterId($conversable)
            || $user->id === static::resolveOwnerId($conversable);
    }

    public function getBookingSummaryAttribute(): array
    {
        $conversable = $this->conversable;

        if (! $conversable) {
            return ['type' => 'unknown', 'label' => 'Conversation', 'title' => '', 'status' => '', 'dates' => '', 'detail_route' => '#'];
        }

        return match (get_class($conversable)) {
            Rental::class => [
                'type' => 'rental',
                'label' => 'Location de véhicule',
                'title' => ($conversable->vehicle ? "{$conversable->vehicle->brand} {$conversable->vehicle->model}" : 'Véhicule'),
                'status' => $conversable->status,
                'dates' => $conversable->start_date?->format('d/m/Y').' - '.$conversable->end_date?->format('d/m/Y'),
                'detail_route' => route('rentals.show', $conversable->id),
                'item_route' => $conversable->vehicle_id ? route('vehicles.show', $conversable->vehicle_id) : null,
            ],
            PropertyBooking::class => [
                'type' => 'property_booking',
                'label' => 'Réservation de propriété',
                'title' => $conversable->property?->title ?? 'Propriété',
                'status' => $conversable->status,
                'dates' => $conversable->checkin_date?->format('d/m/Y').' - '.$conversable->checkout_date?->format('d/m/Y'),
                'detail_route' => route('property-bookings.show', $conversable->id),
                'item_route' => $conversable->property_id ? route('properties.show', $conversable->property_id) : null,
            ],
            EquipmentBooking::class => [
                'type' => 'equipment_booking',
                'label' => 'Réservation de matériel',
                'title' => $conversable->equipment?->name ?? 'Matériel',
                'status' => $conversable->status,
                'dates' => $conversable->start_datetime?->format('d/m/Y H:i').' - '.$conversable->end_datetime?->format('d/m/Y H:i'),
                'detail_route' => route('equipment-bookings.show', $conversable->id),
                'item_route' => $conversable->equipment_id ? route('equipment.show', $conversable->equipment_id) : null,
            ],
            default => ['type' => 'unknown', 'label' => 'Conversation', 'title' => '', 'status' => '', 'dates' => '', 'detail_route' => '#'],
        };
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

    private static function resolveRenterId(Model $conversable): int
    {
        return match (get_class($conversable)) {
            Rental::class => $conversable->renter_id,
            PropertyBooking::class => $conversable->guest_id,
            EquipmentBooking::class => $conversable->renter_id,
            default => throw new \InvalidArgumentException('Unsupported conversable type: '.get_class($conversable)),
        };
    }

    private static function resolveOwnerId(Model $conversable): int
    {
        return match (get_class($conversable)) {
            Rental::class => $conversable->vehicle->owner_id,
            PropertyBooking::class => $conversable->property->owner_id,
            EquipmentBooking::class => $conversable->equipment->owner_id,
            default => throw new \InvalidArgumentException('Unsupported conversable type: '.get_class($conversable)),
        };
    }
}
