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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiry = $this->document->expires_at?->format('M d, Y');

        return (new MailMessage)
            ->subject('Document Expiry Reminder: ' . $this->document->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("{$this->document->title} ({$this->document->document_type}) is approaching expiry.")
            ->line("Expiry date: {$expiry}")
            ->action('Review Document', route('families.documents.index', ['family' => $this->document->family_id]))
            ->line('Please renew or update this document to keep your records accurate.');
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


