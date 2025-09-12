<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
        public Conversation $conversation
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau message de ' . $this->message->sender->name)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Vous avez reçu un nouveau message de ' . $this->message->sender->name . ' concernant votre location.')
            ->line('Message: "' . $this->message->message . '"')
            ->action('Voir le message', route('chat.show', $this->conversation->id))
            ->line('Merci d\'utiliser CarLocation !');
    }

    public function toArray($notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->message->sender->id,
            'sender_name' => $this->message->sender->name,
            'sender_avatar' => $this->message->sender->avatar,
            'message' => $this->message->message,
            'rental_id' => $this->conversation->rental_id,
            'vehicle_info' => [
                'brand' => $this->conversation->rental->vehicle->brand,
                'model' => $this->conversation->rental->vehicle->model,
                'year' => $this->conversation->rental->vehicle->year,
            ],
            'created_at' => $this->message->created_at,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->message->sender->id,
            'sender_name' => $this->message->sender->name,
            'sender_avatar' => $this->message->sender->avatar,
            'message' => $this->message->message,
            'rental_info' => [
                'id' => $this->conversation->rental_id,
                'vehicle' => [
                    'brand' => $this->conversation->rental->vehicle->brand,
                    'model' => $this->conversation->rental->vehicle->model,
                    'year' => $this->conversation->rental->vehicle->year,
                ]
            ],
            'created_at' => $this->message->created_at,
            'type' => 'new_message'
        ]);
    }

    public function broadcastType(): string
    {
        return 'notification.new-message';
    }
}