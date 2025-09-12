<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Rental;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $conversations = Conversation::forUser($user)
            ->with([
                'rental.vehicle',
                'renter:id,name,avatar',
                'owner:id,name,avatar',
                'latestMessage.sender:id,name'
            ])
            ->withCount(['unreadMessagesFor' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->active()
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conversation) use ($user) {
                $otherParticipant = $conversation->getOtherParticipant($user);
                $conversation->other_participant = $otherParticipant;
                $conversation->unread_count = $conversation->getUnreadCountFor($user);
                return $conversation;
            });

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations
        ]);
    }

    public function show(Conversation $conversation)
    {
        $user = Auth::user();
        
        // Check if user is participant
        abort_unless($conversation->isParticipant($user), 403);

        // Load conversation data
        $conversation->load([
            'rental.vehicle',
            'renter:id,name,avatar',
            'owner:id,name,avatar',
            'messages' => function ($query) {
                $query->with('sender:id,name,avatar')
                      ->orderBy('created_at');
            }
        ]);

        // Mark all messages as read for current user
        $conversation->markAllMessagesAsReadFor($user);

        $otherParticipant = $conversation->getOtherParticipant($user);

        return Inertia::render('Chat/Show', [
            'conversation' => $conversation,
            'otherParticipant' => $otherParticipant,
            'messages' => $conversation->messages,
            'rental' => $conversation->rental
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rental_id' => 'required|exists:rentals,id',
            'message' => 'required|string|max:1000'
        ]);

        $rental = Rental::with('vehicle.owner')->findOrFail($validated['rental_id']);
        $user = Auth::user();
        
        // Check if user is participant in this rental
        abort_unless(
            $user->id === $rental->renter_id || $user->id === $rental->vehicle->owner_id,
            403,
            'You are not authorized to send messages for this rental.'
        );

        // Find or create conversation
        $conversation = Conversation::findOrCreateForRental($rental);

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'message_type' => 'text'
        ]);

        $message->load('sender:id,name,avatar');

        // Broadcast the message to other participants
        $this->broadcastMessage($conversation, $message);

        return response()->json([
            'message' => $message,
            'conversation_id' => $conversation->id
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        
        // Check if user is participant
        abort_unless($conversation->isParticipant($user), 403);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'message_type' => 'sometimes|in:text,image,system'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'message_type' => $validated['message_type'] ?? 'text'
        ]);

        $message->load('sender:id,name,avatar');

        // Broadcast the message
        $this->broadcastMessage($conversation, $message);

        return response()->json(['message' => $message]);
    }

    public function getMessages(Conversation $conversation, Request $request)
    {
        $user = Auth::user();
        
        // Check if user is participant
        abort_unless($conversation->isParticipant($user), 403);

        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        $messages = $conversation->messages()
            ->with('sender:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($messages);
    }

    public function markAsRead(Conversation $conversation)
    {
        $user = Auth::user();
        
        // Check if user is participant
        abort_unless($conversation->isParticipant($user), 403);

        $conversation->markAllMessagesAsReadFor($user);

        return response()->json(['success' => true]);
    }

    public function getUnreadCount()
    {
        $user = Auth::user();
        
        $unreadCount = Message::whereHas('conversation', function ($query) use ($user) {
                $query->forUser($user);
            })
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }

    public function createForRental(Request $request, Rental $rental)
    {
        $user = Auth::user();
        
        // Check if user is participant in this rental
        abort_unless(
            $user->id === $rental->renter_id || $user->id === $rental->vehicle->owner_id,
            403
        );

        // Find or create conversation
        $conversation = Conversation::findOrCreateForRental($rental);

        // If this is a new conversation, create a system message
        if ($conversation->wasRecentlyCreated) {
            Message::createSystemMessage(
                $conversation,
                "Conversation démarrée pour la location du véhicule {$rental->vehicle->brand} {$rental->vehicle->model}"
            );
        }

        return redirect()->route('chat.show', $conversation);
    }

    public function archive(Conversation $conversation)
    {
        $user = Auth::user();
        
        // Check if user is participant
        abort_unless($conversation->isParticipant($user), 403);

        $conversation->update(['is_archived' => true]);

        return response()->json(['success' => true]);
    }

    private function broadcastMessage(Conversation $conversation, Message $message)
    {
        // Broadcast to conversation channel
        broadcast(new \App\Events\MessageSent($conversation, $message))->toOthers();
        
        // Send notification to the other participant
        $otherParticipant = $conversation->getOtherParticipant($message->sender);
        $otherParticipant->notify(new NewMessageNotification($message, $conversation));
    }

    private function sendPushNotification(Conversation $conversation, Message $message)
    {
        // Implementation for push notifications
        // This could integrate with services like FCM, OneSignal, etc.
    }
}