<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private CalendarEvent $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Event Reminder: ' . $this->event->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have an upcoming event:')
            ->line($this->event->title)
            ->line($this->event->start_at?->format('M d, Y H:i'))
            ->action('View Calendar', route('families.calendar.index', ['family' => $this->event->family_id]))
            ->line('Reminder set ' . $this->event->reminder_before_minutes . ' minutes before the event.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'family_id' => $this->event->family_id,
            'title' => $this->event->title,
            'start_at' => $this->event->start_at,
            'reminder_before_minutes' => $this->event->reminder_before_minutes,
            'message' => 'Event "' . $this->event->title . '" starts at ' . $this->event->start_at?->format('M d, Y H:i'),
        ];
    }
}

