<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CalendarEvent;
use App\Services\TimezoneService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminder extends Notification
{

    public function __construct(private CalendarEvent $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', \App\Notifications\Channels\DatabaseWithMetaChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Convert UTC times to IST for display in email
        $startAtIst = $this->event->start_at ? TimezoneService::convertUtcToIst($this->event->start_at) : null;
        $startTime = $startAtIst?->format('M d, Y');
        $startTimeFull = $startAtIst?->format('M d, Y h:i A');
        $hoursUntilEvent = $this->event->start_at ? (int)now()->diffInHours($this->event->start_at, false) : null;

        return (new MailMessage)
            ->subject('📅 Event Reminder: ' . $this->event->title)
            ->view('emails.layout', [
                'subject' => 'Event Reminder',
                'headerIcon' => '📅',
                'headerTitle' => 'Event Reminder',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "You have an upcoming event: **{$this->event->title}**",
                ],
                'details' => [
                    'Event' => $this->event->title,
                    'Date & Time (IST)' => $startAtIst?->format('M d, Y h:i A') . ' IST',
                    'Time Until Event' => $hoursUntilEvent !== null && $hoursUntilEvent > 0 ? "{$hoursUntilEvent} hours" : 'Starting soon',
                ],
                'actionUrl' => route('families.calendar.index', ['family' => $this->event->family_id]),
                'actionText' => 'View Calendar',
                'outroLines' => [
                    "This reminder was set {$this->event->reminder_before_minutes} minutes before the event.",
                    'Don\'t forget to attend!',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        // Convert UTC to IST for display in notification
        $startAtIst = $this->event->start_at ? TimezoneService::convertUtcToIst($this->event->start_at) : null;
        
        return [
            'event_id' => $this->event->id,
            'family_id' => $this->event->family_id,
            'title' => $this->event->title,
            'start_at' => $this->event->start_at,
            'reminder_before_minutes' => $this->event->reminder_before_minutes,
            'message' => 'Event "' . $this->event->title . '" starts at ' . $startAtIst?->format('M d, Y h:i A') . ' IST',
        ];
    }
}

