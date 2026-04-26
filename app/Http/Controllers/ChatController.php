<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\EquipmentBooking;
use App\Models\Message;
use App\Models\PropertyBooking;
use App\Models\Rental;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $conversations = Conversation::forUser($user)
            ->with([
                'conversable',
                'renter:id,name,avatar',
                'owner:id,name,avatar',
                'latestMessage.sender:id,name',
            ])
            ->withCount(['messages as unread_messages_count' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->active()
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conversation) use ($user) {
                $conversation->other_participant = $conversation->getOtherParticipant($user);
                $conversation->unread_count = $conversation->unread_messages_count ?? 0;
                $conversation->booking_summary = $conversation->booking_summary;

                return $conversation;
            });

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Conversation $conversation)
    {
        $user = Auth::user();

        abort_unless($conversation->isParticipant($user), 403);

        $conversation->load([
            'conversable',
            'renter:id,name,avatar',
            'owner:id,name,avatar',
            'messages' => function ($query) {
                $query->with('sender:id,name,avatar')->orderBy('created_at');
            },
        ]);

        $conversation->markAllMessagesAsReadFor($user);

        $otherParticipant = $conversation->getOtherParticipant($user);

        return Inertia::render('Chat/Show', [
            'conversation' => $conversation,
            'otherParticipant' => $otherParticipant,
            'messages' => $conversation->messages,
            'bookingSummary' => $conversation->booking_summary,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'conversable_type' => 'required|string|in:App\\Models\\Rental,App\\Models\\PropertyBooking,App\\Models\\EquipmentBooking',
            'conversable_id' => 'required|integer',
            'message' => 'required|string|max:1000',
        ]);

        $conversableClass = $validated['conversable_type'];
        $conversable = $conversableClass::findOrFail($validated['conversable_id']);
        $user = Auth::user();

        abort_unless(
            Conversation::isUserAuthorizedFor($conversable, $user),
            403,
            'You are not authorized to send messages for this booking.'
        );

        $conversation = Conversation::findOrCreateFor($conversable);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'message_type' => 'text',
        ]);

        $message->load('sender:id,name,avatar');

        $this->broadcastMessage($conversation, $message);

        return response()->json([
            'message' => $message,
            'conversation_id' => $conversation->id,
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        abort_unless($conversation->isParticipant($user), 403);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'message_type' => 'sometimes|in:text,image,system',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'message_type' => $validated['message_type'] ?? 'text',
        ]);

        $message->load('sender:id,name,avatar');

        $this->broadcastMessage($conversation, $message);

        return response()->json(['message' => $message]);
    }

    public function getMessages(Conversation $conversation, Request $request)
    {
        $user = Auth::user();

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
        $rental->load('vehicle');

        abort_unless(Conversation::isUserAuthorizedFor($rental, $user), 403);

        $conversation = Conversation::findOrCreateFor($rental);

        if ($conversation->wasRecentlyCreated) {
            Message::createSystemMessage(
                $conversation,
                "Conversation démarrée pour la location du véhicule {$rental->vehicle->brand} {$rental->vehicle->model}"
            );
        }

        return redirect()->route('chat.show', $conversation);
    }

    public function createForPropertyBooking(Request $request, PropertyBooking $booking)
    {
        $user = Auth::user();
        $booking->load('property');

        abort_unless(Conversation::isUserAuthorizedFor($booking, $user), 403);

        $conversation = Conversation::findOrCreateFor($booking);

        if ($conversation->wasRecentlyCreated) {
            Message::createSystemMessage(
                $conversation,
                "Conversation démarrée pour la réservation de la propriété {$booking->property->title}"
            );
        }

        return redirect()->route('chat.show', $conversation);
    }

    public function createForEquipmentBooking(Request $request, EquipmentBooking $booking)
    {
        $user = Auth::user();
        $booking->load('equipment');

        abort_unless(Conversation::isUserAuthorizedFor($booking, $user), 403);

        $conversation = Conversation::findOrCreateFor($booking);

        if ($conversation->wasRecentlyCreated) {
            Message::createSystemMessage(
                $conversation,
                "Conversation démarrée pour la réservation du matériel {$booking->equipment->name}"
            );
        }

        return redirect()->route('chat.show', $conversation);
    }

    public function archive(Conversation $conversation)
    {
        $user = Auth::user();

        abort_unless($conversation->isParticipant($user), 403);

        $conversation->update(['is_archived' => true]);

        return response()->json(['success' => true]);
    }

    private function broadcastMessage(Conversation $conversation, Message $message)
    {
        try {
            broadcast(new \App\Events\MessageSent($conversation, $message))->toOthers();

            $otherParticipant = $conversation->getOtherParticipant($message->sender);
            $otherParticipant->notify(new NewMessageNotification($message, $conversation));
        } catch (\Exception $e) {
            \Log::error('Broadcasting or notification failed: '.$e->getMessage());
        }
    }
}
