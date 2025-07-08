<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Event\Event;

class NewJobFairCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
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
        $event = $this->event;
        $participationUrl = url("/login?redirect=/job-fairs/{$event->id}/participate");
        $recipientName = $notifiable->name ?? 'Valued Partner';
        return (new MailMessage)
            ->subject("Invitation: Participate in the Upcoming Job Fair - {$event->title}")
            ->view('emails.job_fair_custom', [
                'event' => $event,
                'participationUrl' => $participationUrl,
                'recipientName' => $recipientName
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_type' => $this->event->type,
            'event_status' => $this->event->status,
        ];
    }
}