<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Medicine;
use App\Models\MedicineIntakeReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicineIntakeReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Medicine $medicine,
        private readonly MedicineIntakeReminder $reminder
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reminderTime = $this->reminder->reminder_time?->format('h:i A');
        $memberName = $this->reminder->familyMember ? $this->reminder->familyMember->first_name . ' ' . $this->reminder->familyMember->last_name : 'Family Member';
        $frequencyText = match($this->reminder->frequency) {
            'daily' => 'Daily',
            'weekly' => 'Weekly (' . implode(', ', array_map('ucfirst', $this->reminder->days_of_week ?? [])) . ')',
            'custom' => 'Custom dates',
            default => 'Scheduled',
        };

        return (new MailMessage)
            ->subject('💊 Medicine Intake Reminder: ' . $this->medicine->name)
            ->view('emails.layout', [
                'subject' => 'Medicine Intake Reminder',
                'headerIcon' => '💊',
                'headerTitle' => 'Medicine Intake Reminder',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "It's time to take **{$this->medicine->name}**.",
                ],
                'details' => [
                    'Medicine' => $this->medicine->name,
                    'For' => $memberName,
                    'Time' => $reminderTime,
                    'Frequency' => $frequencyText,
                    'Quantity' => $this->medicine->quantity == (int)$this->medicine->quantity ? (int)$this->medicine->quantity : number_format((float)$this->medicine->quantity, 2) . ' ' . $this->medicine->unit,
                ],
                'actionUrl' => route('families.medicines.show', ['family' => $this->medicine->family_id, 'medicine' => $this->medicine->id]),
                'actionText' => 'View Medicine Details',
                'outroLines' => [
                    'Please take your medicine as prescribed.',
                    'Don\'t forget to update your stock after taking the medicine!',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $reminderTime = $this->reminder->reminder_time?->format('h:i A');
        $memberName = $this->reminder->familyMember ? $this->reminder->familyMember->first_name . ' ' . $this->reminder->familyMember->last_name : 'Family Member';

        return [
            'medicine_id' => $this->medicine->id,
            'reminder_id' => $this->reminder->id,
            'family_id' => $this->medicine->family_id,
            'family_member_id' => $this->reminder->family_member_id,
            'title' => 'Medicine Intake Reminder',
            'message' => "Time to take {$this->medicine->name} for {$memberName} at {$reminderTime}",
        ];
    }
}
