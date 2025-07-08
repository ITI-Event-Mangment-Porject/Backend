<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Event\Event;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\RegistrationAndInterview\EventRegistration;
// No need for your custom Notification model here unless you use it in this class

class EventRegistrationSuccess extends Notification implements ShouldQueue
{
    use Queueable;

    public $event;
    public $registration;

    public function __construct(Event $event, EventRegistration $registration)
    {
        $this->event = $event;
        $this->registration = $registration;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // 1. Generate the raw PNG data for the QR code
        // Ensure qr_code_token is a string to prevent issues with QrCode::generate
        $qrCodeToken = $this->registration->qr_code_token ?? ''; 
        $qrCodeData = (string) QrCode::format('png')->size(200)->generate($qrCodeToken);
        $qrCodeName = 'qrcode.png';

        // 2. Build the email message
        return (new MailMessage)
            ->subject("Registration Confirmed: {$this->event->title}")
            ->greeting("Hello {$notifiable->first_name},") // Using first_name is more personal
            ->line("You have successfully registered for: **{$this->event->title}**.")
            ->line("Please find your unique QR code below. This will be required for check-in at the event.")
            
            // 3. THE FIX: Add this line to display the embedded image
            // The Markdown `![Alt text](cid:filename)` tells the email client to display the embedded image here.
            ->line("![QR Code](cid:{$qrCodeName})") 
            
            ->line("---") // A separator for clarity
            ->line("**Event Details:**")
            ->line("📅 **Date:** {$this->event->start_date->format('F j, Y')}")
            ->line("⏰ **Time:** {$this->event->start_time} - {$this->event->end_time}")
            ->line("📍 **Location:** {$this->event->location}")
            ->action('View Event Details', url("/events/{$this->event->slug}")) // Assuming you have a slug
            ->line('We look forward to seeing you there!')
            
            // 4. Attach the data with the same filename used in the line() call above, and make it inline.
            ->attachData($qrCodeData, $qrCodeName, [
                'mime' => 'image/png',
                'as' => $qrCodeName, // This is important for the cid to work correctly
            ]);
    }
}
