<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DoctorVisit;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DoctorVisitReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private DoctorVisit $visit
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', \App\Notifications\Channels\DatabaseWithMetaChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Ensure relationships are loaded
        $this->visit->loadMissing(['familyMember', 'family']);
        
        $memberName = $this->visit->familyMember 
            ? $this->visit->familyMember->first_name . ' ' . $this->visit->familyMember->last_name
            : 'Family Member';
        
        $visitDate = $this->visit->next_visit_date 
            ? Carbon::parse($this->visit->next_visit_date)->format('M d, Y')
            : 'N/A';
        
        $doctorInfo = $this->visit->doctor_name;
        if ($this->visit->clinic_name) {
            $doctorInfo .= ' - ' . $this->visit->clinic_name;
        }
        if ($this->visit->specialization) {
            $doctorInfo .= ' (' . $this->visit->specialization . ')';
        }

        return (new MailMessage)
            ->subject('🏥 Doctor Visit Reminder: ' . $memberName . ' - ' . $visitDate)
            ->view('emails.layout', [
                'subject' => 'Doctor Visit Reminder',
                'headerIcon' => '🏥',
                'headerTitle' => 'Doctor Visit Reminder',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "**{$memberName}** has a scheduled doctor visit **tomorrow**.",
                ],
                'details' => [
                    'Patient' => $memberName,
                    'Visit Date' => $visitDate,
                    'Doctor' => $doctorInfo,
                    'Visit Type' => ucfirst(str_replace('_', ' ', $this->visit->visit_type ?? 'consultation')),
                ],
                'actionUrl' => route('families.health.visits.index', ['family' => $this->visit->family_id]),
                'actionText' => 'View Doctor Visits',
                'outroLines' => [
                    'Please make sure to attend the appointment on time.',
                    'If you need to reschedule, please update the visit details in the system.',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        // Ensure relationships are loaded
        $this->visit->loadMissing(['familyMember', 'family']);
        
        $memberName = $this->visit->familyMember 
            ? $this->visit->familyMember->first_name . ' ' . $this->visit->familyMember->last_name
            : 'Family Member';
        
        $visitDate = $this->visit->next_visit_date 
            ? $this->visit->next_visit_date->format('M d, Y')
            : 'N/A';

        return [
            'type' => 'doctor_visit_reminder',
            'title' => 'Doctor Visit Reminder',
            'message' => "{$memberName} has a scheduled doctor visit tomorrow ({$visitDate}).",
            'visit_id' => $this->visit->id,
            'family_id' => $this->visit->family_id,
            'family_name' => $this->visit->family->name ?? 'Unknown',
            'family_member_id' => $this->visit->family_member_id,
            'member_name' => $memberName,
            'visit_date' => $this->visit->next_visit_date?->format('Y-m-d'),
            'doctor_name' => $this->visit->doctor_name,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}

