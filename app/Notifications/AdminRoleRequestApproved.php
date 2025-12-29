<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AdminRoleRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminRoleRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private AdminRoleRequest $request,
        private User $approver
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
            ->subject('✅ Admin Role Approved: ' . $family->name)
            ->view('emails.layout', [
                'subject' => 'Admin Role Approved',
                'headerIcon' => '✅',
                'headerTitle' => 'Admin Role Approved',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "Your admin role request for **{$family->name}** has been approved by **{$this->approver->name}**.",
                    'You now have admin privileges for this family.',
                ],
                'details' => [
                    'Family' => $family->name,
                    'Approved By' => $this->approver->name,
                    'Approved At' => now()->format('M d, Y h:i A'),
                ],
                'actionUrl' => route('families.show', $family->id),
                'actionText' => 'View Family',
                'outroLines' => [
                    'You can now manage family members, roles, and other administrative tasks.',
                    'Thank you for being part of the Family ERP community!',
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
            'type' => 'admin_role_approved',
            'request_id' => $this->request->id,
            'family_id' => $this->request->family_id,
            'family_name' => $this->request->family->name,
            'approved_by_user_id' => $this->approver->id,
            'approved_by_user_name' => $this->approver->name,
        ];
    }
}
