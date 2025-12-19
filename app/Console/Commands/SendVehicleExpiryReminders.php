<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\VehicleReminder;
use App\Models\Family;
use App\Models\User;
use App\Notifications\VehicleExpiryReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendVehicleExpiryReminders extends Command
{
    protected $signature = 'vehicles:send-expiry-reminders';

    protected $description = 'Send reminders for vehicles with expiring RC, insurance, or PUC';

    public function handle(): int
    {
        $today = now()->toDateString();
        $reminders = VehicleReminder::with(['vehicle.family.roles.user', 'vehicle.familyMember.user'])
            ->whereDate('remind_at', '<=', $today)
            ->whereNull('sent_at')
            ->limit(200)
            ->get();

        $sentCount = 0;

        foreach ($reminders as $reminder) {
            $vehicle = $reminder->vehicle;

            if (!$vehicle) {
                $reminder->delete();
                continue;
            }

            $expiryDate = $this->getExpiryDate($vehicle, $reminder->reminder_type);
            if (!$expiryDate) {
                $reminder->delete();
                continue;
            }

            $recipients = $this->buildRecipients($reminder);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new VehicleExpiryReminder($vehicle, $reminder->reminder_type));
                $sentCount += $recipients->count();
            }

            $reminder->update(['sent_at' => now()]);
        }

        $this->info("Sent {$sentCount} vehicle expiry reminders.");

        return Command::SUCCESS;
    }

    private function buildRecipients(VehicleReminder $reminder): Collection
    {
        $users = collect();
        $family = $reminder->vehicle?->family;

        if (!$family) {
            return $users;
        }

        // Get users from family roles
        $family->roles()->with('user')->get()->each(function ($role) use ($users) {
            if ($role->user instanceof User) {
                $users->push($role->user);
            }
        });

        // Get users from family members
        $family->members()->with('user')->get()->each(function ($member) use ($users) {
            if ($member->user instanceof User) {
                $users->push($member->user);
            }
        });

        return $users->unique('id');
    }

    private function getExpiryDate($vehicle, string $reminderType): ?\Carbon\Carbon
    {
        return match ($reminderType) {
            'rc_expiry' => $vehicle->rc_expiry_date,
            'insurance_expiry' => $vehicle->insurance_expiry_date,
            'puc_expiry' => $vehicle->puc_expiry_date,
            default => null,
        };
    }
}







