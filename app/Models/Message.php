<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'message',
        'message_type',
        'attachments',
        'read_at',
        'is_edited',
        'edited_at'
    ];

    protected $casts = [
        'attachments' => 'array',
        'read_at' => 'datetime',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime'
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isReadBy(User $user): bool
    {
        // A message is considered read if:
        // 1. It was sent by the user themselves, OR
        // 2. It has a read_at timestamp and the user is not the sender
        return $this->user_id === $user->id || 
               ($this->read_at !== null && $this->user_id !== $user->id);
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isSystemMessage(): bool
    {
        return $this->message_type === 'system';
    }

    public function isImageMessage(): bool
    {
        return $this->message_type === 'image';
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function scopeUnread($query, User $user)
    {
        return $query->where('user_id', '!=', $user->id)
                    ->whereNull('read_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('H:i');
    }

    public function getFormattedFullDateAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    public static function createSystemMessage(Conversation $conversation, string $message): self
    {
        return static::create([
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->renter_id, // Default to renter for system messages
            'message' => $message,
            'message_type' => 'system',
            'read_at' => now() // System messages are auto-read
        ]);
    }

    protected static function booted()
    {
        static::created(function ($message) {
            // Update conversation's last_message_at when a new message is created
            $message->conversation->update(['last_message_at' => $message->created_at]);
        });
    }
}