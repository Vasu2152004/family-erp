<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleExpiryReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Vehicle $vehicle,
        private readonly string $reminderType
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiryDate = $this->getExpiryDate();
        $expiry = $expiryDate?->format('M d, Y');
        $typeLabel = $this->getTypeLabel();
        $vehicleName = "{$this->vehicle->make} {$this->vehicle->model} ({$this->vehicle->registration_number})";
        $daysUntilExpiry = $expiryDate ? (int)now()->diffInDays($expiryDate, false) : null;
        $icon = match($this->reminderType) {
            'rc_expiry' => '🚗',
            'insurance_expiry' => '🛡️',
            'puc_expiry' => '🌿',
            default => '🚙',
        };

        return (new MailMessage)
            ->subject("{$icon} Vehicle {$typeLabel} Expiry Reminder: {$vehicleName}")
            ->view('emails.layout', [
                'subject' => "Vehicle {$typeLabel} Expiry Reminder",
                'headerIcon' => $icon,
                'headerTitle' => 'Vehicle Expiry Reminder',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "Your vehicle **{$vehicleName}** has an expiring {$typeLabel}.",
                ],
                'details' => [
                    'Vehicle' => $vehicleName,
                    'Document Type' => $typeLabel,
                    'Expiry Date' => $expiry,
                    'Days Remaining' => $daysUntilExpiry !== null && $daysUntilExpiry > 0 ? "{$daysUntilExpiry} days" : ($daysUntilExpiry === 0 ? 'Expires today' : 'Expired'),
                ],
                'actionUrl' => route('families.vehicles.show', ['family' => $this->vehicle->family_id, 'vehicle' => $this->vehicle->id]),
                'actionText' => 'View Vehicle Details',
                'outroLines' => [
                    'Please renew or update this document to keep your vehicle records accurate.',
                    '',
                    '<small style="color: #718096;">Reminder Schedule: You will receive reminders at 30 days, 7 days, and on the expiry date.</small>',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $expiryDate = $this->getExpiryDate();
        $typeLabel = $this->getTypeLabel();
        $vehicleName = "{$this->vehicle->make} {$this->vehicle->model} ({$this->vehicle->registration_number})";

        return [
            'vehicle_id' => $this->vehicle->id,
            'family_id' => $this->vehicle->family_id,
            'reminder_type' => $this->reminderType,
            'vehicle_name' => $vehicleName,
            'expiry_date' => $expiryDate,
            'message' => "{$vehicleName} - {$typeLabel} expires on {$expiryDate?->format('M d, Y')}",
        ];
    }

    private function getExpiryDate(): ?\Carbon\Carbon
    {
        return match ($this->reminderType) {
            'rc_expiry' => $this->vehicle->rc_expiry_date,
            'insurance_expiry' => $this->vehicle->insurance_expiry_date,
            'puc_expiry' => $this->vehicle->puc_expiry_date,
            default => null,
        };
    }

    private function getTypeLabel(): string
    {
        return match ($this->reminderType) {
            'rc_expiry' => 'RC (Registration Certificate)',
            'insurance_expiry' => 'Insurance',
            'puc_expiry' => 'PUC (Pollution Under Control)',
            default => 'Document',
        };
    }
}








