<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Medicine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicineExpiryReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Medicine $medicine)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiry = $this->medicine->expiry_date?->format('M d, Y');
        $daysUntilExpiry = (int)now()->diffInDays($this->medicine->expiry_date, false);

        return (new MailMessage)
            ->subject('💊 Medicine Expiry Reminder: ' . $this->medicine->name)
            ->view('emails.layout', [
                'subject' => 'Medicine Expiry Reminder',
                'headerIcon' => '💊',
                'headerTitle' => 'Medicine Expiry Reminder',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "Your medicine **{$this->medicine->name}** is approaching expiry.",
                ],
                'details' => [
                    'Medicine' => $this->medicine->name,
                    'Manufacturer' => $this->medicine->manufacturer ?? 'N/A',
                    'Batch Number' => $this->medicine->batch_number ?? 'N/A',
                    'Quantity' => $this->medicine->quantity == (int)$this->medicine->quantity ? (int)$this->medicine->quantity : number_format((float)$this->medicine->quantity, 2) . ' ' . $this->medicine->unit,
                    'Expiry Date' => $expiry,
                    'Days Remaining' => $daysUntilExpiry > 0 ? "{$daysUntilExpiry} days" : 'Expired',
                ],
                'actionUrl' => route('families.medicines.show', ['family' => $this->medicine->family_id, 'medicine' => $this->medicine->id]),
                'actionText' => 'View Medicine Details',
                'outroLines' => [
                    'Please check and dispose of expired medicines safely.',
                    '',
                    '<small style="color: #718096;">Reminder Schedule: You will receive reminders at 30 days, 7 days, and on the expiry date.</small>',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $expiry = $this->medicine->expiry_date?->format('M d, Y');
        $daysUntilExpiry = (int)now()->diffInDays($this->medicine->expiry_date, false);

        return [
            'medicine_id' => $this->medicine->id,
            'family_id' => $this->medicine->family_id,
            'name' => $this->medicine->name,
            'expiry_date' => $this->medicine->expiry_date,
            'message' => "{$this->medicine->name} expires on {$expiry} ({$daysUntilExpiry} days remaining)",
        ];
    }
}
