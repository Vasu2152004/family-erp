<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MedicineReminder;
use App\Models\Prescription;
use App\Services\TimezoneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HealthMedicineReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Prescription $prescription,
        private readonly MedicineReminder $reminder
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', \App\Notifications\Channels\DatabaseWithMetaChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Convert UTC reminder_time to IST for display
        $reminderTime = $this->reminder->reminder_time 
            ? TimezoneService::convertUtcToIst($this->reminder->reminder_time)->format('h:i A') . ' IST'
            : null;
        $memberName = $this->reminder->familyMember 
            ? $this->reminder->familyMember->first_name . ' ' . $this->reminder->familyMember->last_name 
            : 'Family Member';
        
        $frequencyText = match($this->reminder->frequency) {
            'daily' => 'Daily',
            'weekly' => 'Weekly (' . implode(', ', array_map('ucfirst', $this->reminder->days_of_week ?? [])) . ')',
            default => 'Scheduled',
        };

        return (new MailMessage)
            ->subject('💊 Medicine Reminder: ' . $this->prescription->medication_name)
            ->view('emails.layout', [
                'subject' => 'Medicine Reminder',
                'headerIcon' => '💊',
                'headerTitle' => 'Medicine Reminder',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "It's time to take **{$this->prescription->medication_name}**.",
                ],
                'details' => [
                    'Medication' => $this->prescription->medication_name,
                    'For' => $memberName,
                    'Dosage' => $this->prescription->dosage ?? 'N/A',
                    'Time (IST)' => $reminderTime,
                    'Frequency' => $frequencyText,
                ],
                'actionUrl' => route('families.health.visits.show', ['family' => $this->prescription->family_id, 'visit' => $this->prescription->doctor_visit_id]),
                'actionText' => 'View Prescription Details',
                'outroLines' => [
                    'Please take your medicine as prescribed.',
                    $this->prescription->instructions ? "Instructions: {$this->prescription->instructions}" : '',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        // Convert UTC reminder_time to IST for display
        $reminderTime = $this->reminder->reminder_time 
            ? TimezoneService::convertUtcToIst($this->reminder->reminder_time)->format('h:i A') . ' IST'
            : null;
        $memberName = $this->reminder->familyMember 
            ? $this->reminder->familyMember->first_name . ' ' . $this->reminder->familyMember->last_name 
            : 'Family Member';

        return [
            'prescription_id' => $this->prescription->id,
            'reminder_id' => $this->reminder->id,
            'family_id' => $this->prescription->family_id,
            'family_member_id' => $this->reminder->family_member_id,
            'title' => 'Medicine Reminder',
            'message' => "Time to take {$this->prescription->medication_name} for {$memberName} at {$reminderTime}",
        ];
    }
}

