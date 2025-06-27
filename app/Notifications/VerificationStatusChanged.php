<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerificationStatusChanged extends Notification
{
    use Queueable;

    protected $status;

    /**
     * Create a new notification instance.
     */
    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Verification Status Updated')
            ->greeting('Hello ' . $notifiable->name . ',');

        if ($this->status === 'verified') {
            $message->line('🎉 Congratulations! Your verification request has been accepted.');
        } elseif ($this->status === 'unverified') {
            $message->line('😔 Unfortunately, your verification request has been rejected. Please re-check your documents and try again.');
        } else {
            $message->line('Your verification status is now: ' . ucfirst($this->status) . '.');
        }

        return $message->line('Thank you for using ' . config('app.name') . '!');
    }

    /**
     * Optional: get array version (if you use DB notifications later)
     */
    public function toArray(object $notifiable): array
    {
        return [
            'verification_status' => $this->status,
        ];
    }
}
