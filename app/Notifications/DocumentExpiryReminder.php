<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiryReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Document $document)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', \App\Notifications\Channels\DatabaseWithMetaChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiry = $this->document->expires_at?->format('M d, Y');
        $daysUntilExpiry = (int)now()->diffInDays($this->document->expires_at, false);

        return (new MailMessage)
            ->subject('📄 Document Expiry Reminder: ' . $this->document->title)
            ->view('emails.layout', [
                'subject' => 'Document Expiry Reminder',
                'headerIcon' => '📄',
                'headerTitle' => 'Document Expiry Reminder',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "Your document **{$this->document->title}** ({$this->document->document_type}) is approaching expiry.",
                ],
                'details' => [
                    'Document' => $this->document->title,
                    'Type' => ucfirst(str_replace('_', ' ', $this->document->document_type)),
                    'Expiry Date' => $expiry,
                    'Days Remaining' => $daysUntilExpiry > 0 ? "{$daysUntilExpiry} days" : 'Expired',
                ],
                'actionUrl' => route('families.documents.index', ['family' => $this->document->family_id]),
                'actionText' => 'Review Document',
                'outroLines' => [
                    'Please renew or update this document to keep your records accurate.',
                    '',
                    '<small style="color: #718096;">Reminder Schedule: You will receive reminders at 30 days, 7 days, and on the expiry date.</small>',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'family_id' => $this->document->family_id,
            'title' => $this->document->title,
            'document_type' => $this->document->document_type,
            'expires_at' => $this->document->expires_at,
            'message' => "{$this->document->title} expires on {$this->document->expires_at?->format('M d, Y')}",
        ];
    }
}













