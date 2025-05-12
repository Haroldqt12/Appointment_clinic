<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Booking;

class RescheduledAppointmentNotification extends Notification
{
    use Queueable;

    protected $booking;
    protected $reason;
    protected $originalDate;
    protected $originalTime;

    public function __construct(Booking $booking, $reason, $originalDate = null, $originalTime = null)
    {
        $this->booking = $booking;
        $this->reason = $reason;
        $this->originalDate = $originalDate;
        $this->originalTime = $originalTime;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // ← add email here
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Appointment Has Been Rescheduled')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your appointment originally scheduled for **{$this->originalDate} at {$this->originalTime}**")
            ->line("has been **rescheduled** to **{$this->booking->date} at {$this->booking->time}**.")
            ->line("📌 Reason: {$this->reason}")
            ->line("Please check your appointment history for details.")
            ->salutation('Thank you for your understanding, - Medicare Clinic');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Your appointment originally scheduled for {$this->originalDate} at {$this->originalTime} 
                          has been rescheduled to {$this->booking->date} at {$this->booking->time}. Reason: {$this->reason}",
            'booking_id' => $this->booking->BookingId,
            'url' => route('user.history'),
        ];
    }
}
