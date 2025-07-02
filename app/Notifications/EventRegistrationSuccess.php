<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Event\Event;
use App\Models\RegistrationAndInterview\EventRegistration;
use App\Models\NotificationsAndMessaging\Notification as CustomNotification;

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

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // 'database' will use your custom table
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Registration Confirmed: {$this->event->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have successfully registered for: **{$this->event->title}**")
            ->line("📅 Date: {$this->event->start_date->format('F j, Y')}")
            ->line("⏰ Time: {$this->event->start_time} - {$this->event->end_time}")
            ->line("📍 Location: {$this->event->location}")
            ->action('View Event Details', url("/events/{$this->event->slug}"))
            ->line('Thank you for registering!');
    }

    /**
     * Store notification in your custom notifications table
     */
    public function toDatabase(object $notifiable): array
    {
        // This will be handled by the custom notification creation
       
        return [];
    }
}
