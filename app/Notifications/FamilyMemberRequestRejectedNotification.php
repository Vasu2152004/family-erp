<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FamilyMemberRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyMemberRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private FamilyMemberRequest $request
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
        $this->request->loadMissing(['family', 'requestedUser', 'requestedBy']);
        
        $family = $this->request->family;
        $memberName = $this->request->first_name . ' ' . $this->request->last_name;
        $rejectedBy = $this->request->requestedUser;

        return (new MailMessage)
            ->subject('❌ Family Member Request Rejected: ' . $memberName)
            ->view('emails.layout', [
                'subject' => 'Family Member Request Rejected',
                'headerIcon' => '❌',
                'headerTitle' => 'Family Member Request Rejected',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "**{$rejectedBy->name}** has rejected your request to add **{$memberName}** as a family member to **{$family->name}**.",
                ],
                'details' => [
                    'Family' => $family->name,
                    'Member Name' => $memberName,
                    'Relation' => $this->request->relation,
                    'Rejected By' => $rejectedBy->name,
                ],
                'actionUrl' => route('families.show', $family),
                'actionText' => 'View Family',
                'outroLines' => [
                    'If you have any questions about this decision, please contact the family admin.',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'family_member_request_rejected',
            'request_id' => $this->request->id,
            'family_id' => $this->request->family_id,
            'family_name' => $this->request->family->name,
            'member_name' => $this->request->first_name . ' ' . $this->request->last_name,
        ];
    }
}
