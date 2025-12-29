<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AdminRoleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminRoleRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private AdminRoleRequest $request
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
        $this->request->loadMissing(['user', 'family']);
        
        $requestingUser = $this->request->user;
        $family = $this->request->family;

        return (new MailMessage)
            ->subject('🔐 Admin Role Request: ' . $requestingUser->name)
            ->view('emails.layout', [
                'subject' => 'Admin Role Request',
                'headerIcon' => '🔐',
                'headerTitle' => 'Admin Role Request',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "**{$requestingUser->name}** has requested admin role for **{$family->name}**.",
                    "This is request #{$this->request->request_count} of 3.",
                ],
                'details' => [
                    'Family' => $family->name,
                    'Requesting User' => $requestingUser->name,
                    'Request Count' => $this->request->request_count . ' / 3',
                    'Requested At' => $this->request->last_requested_at->format('M d, Y h:i A'),
                ],
                'actionUrl' => route('families.show', $family->id),
                'actionText' => 'View Family',
                'outroLines' => [
                    'Please review the request and approve or reject it.',
                    'If no admin responds after 3 requests, the user will be automatically promoted.',
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
            'type' => 'admin_role_request',
            'request_id' => $this->request->id,
            'family_id' => $this->request->family_id,
            'family_name' => $this->request->family->name,
            'requesting_user_id' => $this->request->user_id,
            'requesting_user_name' => $this->request->user->name,
            'request_count' => $this->request->request_count,
        ];
    }
}
