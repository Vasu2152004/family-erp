<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MedicineReminder;
use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Prescription $prescription, private readonly MedicineReminder $reminder)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Medicine Reminder: ' . $this->prescription->medication_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a reminder to take your medicine.')
            ->line('Medicine: ' . $this->prescription->medication_name)
            ->line('Dosage: ' . ($this->prescription->dosage ?? 'As advised'))
            ->line('Instructions: ' . ($this->prescription->instructions ?? 'Follow prescription.'))
            ->line('Scheduled time: ' . ($this->reminder->reminder_time ?? ''))
            ->action('View Health', route('families.health.visits.show', [$this->prescription->family_id, $this->prescription->doctor_visit_id]));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'prescription_id' => $this->prescription->id,
            'family_id' => $this->prescription->family_id,
            'family_member_id' => $this->prescription->family_member_id,
            'reminder_id' => $this->reminder->id,
            'title' => 'Medicine Reminder: ' . $this->prescription->medication_name,
            'message' => 'Time to take ' . $this->prescription->medication_name . ' (' . ($this->prescription->dosage ?? 'as advised') . ')',
        ];
    }
}

