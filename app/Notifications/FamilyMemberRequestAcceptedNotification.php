<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FamilyMemberRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyMemberRequestAcceptedNotification extends Notification implements ShouldQueue
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
        $acceptedBy = $this->request->requestedUser;

        return (new MailMessage)
            ->subject('✅ Family Member Request Accepted: ' . $memberName)
            ->view('emails.layout', [
                'subject' => 'Family Member Request Accepted',
                'headerIcon' => '✅',
                'headerTitle' => 'Family Member Request Accepted',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "**{$acceptedBy->name}** has accepted your request to add **{$memberName}** as a family member to **{$family->name}**.",
                    "The member has been successfully added to the family.",
                ],
                'details' => [
                    'Family' => $family->name,
                    'Member Name' => $memberName,
                    'Relation' => $this->request->relation,
                    'Accepted By' => $acceptedBy->name,
                ],
                'actionUrl' => route('families.show', $family),
                'actionText' => 'View Family',
                'outroLines' => [
                    'You can now manage this member\'s information, health records, and more.',
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
            'type' => 'family_member_request_accepted',
            'request_id' => $this->request->id,
            'family_id' => $this->request->family_id,
            'family_name' => $this->request->family->name,
            'member_name' => $this->request->first_name . ' ' . $this->request->last_name,
        ];
    }
}
