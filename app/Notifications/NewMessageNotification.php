<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
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
        $summary = $this->conversation->booking_summary;

        return (new MailMessage)
            ->subject('Nouveau message de '.$this->message->sender->name)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Vous avez reçu un nouveau message de '.$this->message->sender->name." concernant votre {$summary['label']}.")
            ->line('Message: "'.$this->message->message.'"')
            ->action('Voir le message', route('chat.show', $this->conversation->id))
            ->line('Merci d\'utiliser notre plateforme !');
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
            'booking_summary' => $this->conversation->booking_summary,
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
            'booking_summary' => $this->conversation->booking_summary,
            'created_at' => $this->message->created_at,
            'type' => 'new_message',
        ]);
    }

    public function broadcastType(): string
    {
        return 'notification.new-message';
    }
}
