<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Welcome to Family ERP!')
            ->view('emails.layout', [
                'subject' => 'Welcome to Family ERP',
                'headerIcon' => '🎉',
                'headerTitle' => 'Welcome to Family ERP!',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    'Thank you for joining Family ERP! We\'re excited to have you on board.',
                    'Family ERP helps you manage your family\'s finances, health records, documents, and much more in one place.',
                ],
                'details' => [
                    'Your Account' => $notifiable->email,
                    'Getting Started' => 'Create your first family or join an existing one',
                ],
                'actionUrl' => route('dashboard'),
                'actionText' => 'Go to Dashboard',
                'outroLines' => [
                    'If you have any questions, feel free to explore the application or contact our support team.',
                    'We hope you enjoy using Family ERP!',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }
}
