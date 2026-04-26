<?php

namespace App\Notifications;

use App\Models\PropertyBooking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingReceived extends Notification
{
    public function __construct(
        public PropertyBooking $booking
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $property = $this->booking->property;
        $guest = $this->booking->guest;
        $amount = number_format($this->booking->total_amount / 100, 2, ',', ' ').' €';

        return (new MailMessage)
            ->subject('Nouvelle réservation pour '.$property->title)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Vous avez reçu une nouvelle réservation confirmée.')
            ->line('**Propriété :** '.$property->title)
            ->line('**Voyageur :** '.$guest->name)
            ->line('**Arrivée :** '.$this->booking->checkin_date->format('d/m/Y'))
            ->line('**Départ :** '.$this->booking->checkout_date->format('d/m/Y'))
            ->line('**Voyageurs :** '.$this->booking->guests_count)
            ->line('**Montant :** '.$amount)
            ->action('Voir la réservation', route('property-bookings.show', $this->booking->id))
            ->line('Vous pouvez contacter le voyageur via la messagerie.');
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'type' => 'new_booking',
            'message' => 'Nouvelle réservation de '.$this->booking->guest->name.' pour '.$this->booking->property->title,
            'property_title' => $this->booking->property->title,
            'guest_name' => $this->booking->guest->name,
            'checkin' => $this->booking->checkin_date->format('d/m/Y'),
            'checkout' => $this->booking->checkout_date->format('d/m/Y'),
        ];
    }
}
