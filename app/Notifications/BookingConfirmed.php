<?php

namespace App\Notifications;

use App\Models\PropertyBooking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification
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
        $amount = number_format($this->booking->total_amount / 100, 2, ',', ' ').' €';

        return (new MailMessage)
            ->subject('Confirmation de votre réservation - '.$property->title)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre réservation a été confirmée et le paiement a bien été reçu.')
            ->line('**Propriété :** '.$property->title)
            ->line('**Adresse :** '.$property->city)
            ->line('**Arrivée :** '.$this->booking->checkin_date->format('d/m/Y'))
            ->line('**Départ :** '.$this->booking->checkout_date->format('d/m/Y'))
            ->line('**Voyageurs :** '.$this->booking->guests_count)
            ->line('**Total payé :** '.$amount)
            ->action('Voir ma réservation', route('property-bookings.show', $this->booking->id))
            ->line('Vous pouvez contacter le propriétaire via la messagerie pour organiser votre arrivée.')
            ->line('Merci d\'utiliser notre plateforme !');
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'type' => 'booking_confirmed',
            'message' => 'Votre réservation pour '.$this->booking->property->title.' est confirmée.',
            'property_title' => $this->booking->property->title,
            'checkin' => $this->booking->checkin_date->format('d/m/Y'),
            'checkout' => $this->booking->checkout_date->format('d/m/Y'),
        ];
    }
}
