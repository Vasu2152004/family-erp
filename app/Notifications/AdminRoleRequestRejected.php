<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AdminRoleRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminRoleRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private AdminRoleRequest $request,
        private User $rejector
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
        
        $family = $this->request->family;

        return (new MailMessage)
            ->subject('❌ Admin Role Request Rejected: ' . $family->name)
            ->view('emails.layout', [
                'subject' => 'Admin Role Request Rejected',
                'headerIcon' => '❌',
                'headerTitle' => 'Admin Role Request Rejected',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "Your admin role request for **{$family->name}** has been rejected by **{$this->rejector->name}**.",
                ],
                'details' => [
                    'Family' => $family->name,
                    'Rejected By' => $this->rejector->name,
                    'Rejected At' => now()->format('M d, Y h:i A'),
                ],
                'actionUrl' => route('families.show', $family->id),
                'actionText' => 'View Family',
                'outroLines' => [
                    'If you have questions about this decision, please contact the family owner or another admin.',
                    'You can still request admin role again in the future if needed.',
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
            'type' => 'admin_role_rejected',
            'request_id' => $this->request->id,
            'family_id' => $this->request->family_id,
            'family_name' => $this->request->family->name,
            'rejected_by_user_id' => $this->rejector->id,
            'rejected_by_user_name' => $this->rejector->name,
        ];
    }
}
