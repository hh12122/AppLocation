<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for conversations
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Log authentication attempt for debugging
    \Log::info('Broadcasting auth attempt', [
        'user_id' => $user ? $user->id : null,
        'conversation_id' => $conversationId,
    ]);

    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        \Log::warning('Conversation not found', ['conversation_id' => $conversationId]);
        return false;
    }

    $isParticipant = $conversation->isParticipant($user);

    \Log::info('Broadcasting auth result', [
        'user_id' => $user->id,
        'is_participant' => $isParticipant,
    ]);

    // Only allow participants to join the conversation channel
    return $isParticipant;
});

// Presence channel for online status (optional)
Broadcast::channel('chat-presence', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
    ];
});
