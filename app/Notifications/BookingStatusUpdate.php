<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class BookingStatusUpdate extends Notification
{
    use Queueable;
    protected $booking;
    /**
     * Create a new notification instance.
     */
    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Your Booking Status Has Been Updated')
                    ->line('The status of your booking (ID: ' . $this->booking->id . ') has been updated to: **' . $this->booking->status . '**.')
                    ->line('Notes: ' . ($this->booking->verification_notes ?? 'None'))
                    ->action('View Booking', url('/bookings/' . $this->booking->id))
                    ->line('Thank you for using our service!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'booking_id' => $this->booking->id,
            'status' => $this->booking->status,
            'notes' => $this->booking->verification_notes,
            'message' => 'Your booking status has been updated to: ' . $this->booking->status,
            'link' => url('/bookings/' . $this->booking->id),
        ];
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'status' => $this->booking->status,
            'notes' => $this->booking->verification_notes,
            'message' => 'Your booking status has been updated to: ' . $this->booking->status,
            'link' => url('/bookings/' . $this->booking->id),
        ];
    }
}
