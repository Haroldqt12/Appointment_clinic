<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AppointmentConfirmedNotification extends Notification
{
    use Queueable;

    protected $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // ← now sends email + in-app
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Appointment is Confirmed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your appointment has been confirmed by Medicare Clinic.')
            ->line('👨‍⚕️ Doctor: Dr. ' . $this->booking->doctor->firstname . ' ' . $this->booking->doctor->lastname)
            ->line('📅 Date: ' . $this->booking->date)
            ->line('⏰ Time: ' . $this->booking->time)
            ->line('If you have any questions, please contact us.')
            ->salutation('Thank you for choosing Medicare Clinic!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Your appointment with Dr. ' . $this->booking->doctor->firstname . ' has been confirmed.',
            'booking_id' => $this->booking->BookingId,
        ];
    }
}
