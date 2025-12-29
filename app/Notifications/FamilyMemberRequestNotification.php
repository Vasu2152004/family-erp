<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FamilyMemberRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyMemberRequestNotification extends Notification implements ShouldQueue
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
        
        $requestedBy = $this->request->requestedBy;
        $family = $this->request->family;
        $memberName = $this->request->first_name . ' ' . $this->request->last_name;

        return (new MailMessage)
            ->subject('👨‍👩‍👧‍👦 Family Member Request: ' . $memberName)
            ->view('emails.layout', [
                'subject' => 'Family Member Request',
                'headerIcon' => '👨‍👩‍👧‍👦',
                'headerTitle' => 'Family Member Request',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "**{$requestedBy->name}** has sent you a request to add **{$memberName}** as a family member to **{$family->name}**.",
                ],
                'details' => [
                    'Family' => $family->name,
                    'Member Name' => $memberName,
                    'Relation' => $this->request->relation,
                    'Requested By' => $requestedBy->name,
                ],
                'actionUrl' => route('family-member-requests.index'),
                'actionText' => 'View Request',
                'outroLines' => [
                    'Please review the request and accept or reject it.',
                    'Once accepted, the member will be added to your family.',
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
            'type' => 'family_member_request',
            'request_id' => $this->request->id,
            'family_id' => $this->request->family_id,
            'family_name' => $this->request->family->name,
            'member_name' => $this->request->first_name . ' ' . $this->request->last_name,
            'requested_by' => $this->request->requestedBy->name,
        ];
    }
}
