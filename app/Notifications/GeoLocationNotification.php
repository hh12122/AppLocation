<?php

namespace App\Notifications;

use App\Models\GeoNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class GeoLocationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly GeoNotification $geoNotification
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        if ($notifiable->notificationPreferences?->canReceiveEmailNotification()) {
            $channels[] = 'mail';
        }

        if ($notifiable->notificationPreferences?->canReceivePushNotification()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $actionUrl = $this->getActionUrl();
        
        return (new MailMessage)
            ->subject($this->geoNotification->title)
            ->greeting("Hello {$notifiable->name}!")
            ->line($this->geoNotification->message)
            ->when($this->geoNotification->location_name, function ($mail) {
                $mail->line("Location: {$this->geoNotification->location_name}");
            })
            ->when($actionUrl, function ($mail) use ($actionUrl) {
                $mail->action('View Details', $actionUrl);
            })
            ->line('Thank you for using AppLocation!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'geo_notification_id' => $this->geoNotification->id,
            'type' => $this->geoNotification->type,
            'title' => $this->geoNotification->title,
            'message' => $this->geoNotification->message,
            'location' => [
                'latitude' => $this->geoNotification->latitude,
                'longitude' => $this->geoNotification->longitude,
                'name' => $this->geoNotification->location_name,
            ],
            'data' => $this->geoNotification->data,
            'action_url' => $this->getActionUrl(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'geo_notification_id' => $this->geoNotification->id,
            'type' => $this->geoNotification->type,
            'title' => $this->geoNotification->title,
            'message' => $this->geoNotification->message,
            'location' => [
                'latitude' => (float) $this->geoNotification->latitude,
                'longitude' => (float) $this->geoNotification->longitude,
                'name' => $this->geoNotification->location_name,
            ],
            'data' => $this->geoNotification->data,
            'action_url' => $this->getActionUrl(),
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the action URL based on notification type and data
     */
    private function getActionUrl(): ?string
    {
        // Check if action_url is provided in data
        if ($this->geoNotification->data && isset($this->geoNotification->data['action_url'])) {
            return $this->geoNotification->data['action_url'];
        }

        // Generate URL based on notification type
        return match ($this->geoNotification->type) {
            'nearby_rental' => $this->geoNotification->data['vehicle_id'] 
                ? route('vehicles.show', $this->geoNotification->data['vehicle_id'])
                : route('vehicles.index'),
            'pickup_reminder' => $this->geoNotification->data['rental_id']
                ? route('rentals.show', $this->geoNotification->data['rental_id'])
                : route('my-rentals'),
            'new_listing' => $this->geoNotification->data['vehicle_id']
                ? route('vehicles.show', $this->geoNotification->data['vehicle_id'])
                : route('vehicles.index'),
            'price_drop' => $this->geoNotification->data['vehicle_id']
                ? route('vehicles.show', $this->geoNotification->data['vehicle_id'])
                : route('favorites.index'),
            default => null,
        };
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType($notifiable): string
    {
        return 'geo_location_notification';
    }
}