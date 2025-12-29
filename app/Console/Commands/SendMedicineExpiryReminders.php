<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MedicineExpiryReminder;
use App\Notifications\MedicineExpiryReminder as MedicineExpiryReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendMedicineExpiryReminders extends Command
{
    protected $signature = 'medicines:send-expiry-reminders';

    protected $description = 'Send reminders for medicines approaching expiry';

    public function handle(): int
    {
        $today = now()->toDateString();
        $reminders = MedicineExpiryReminder::with(['medicine.family.roles.user', 'medicine.family.members.user'])
            ->whereDate('remind_at', '<=', $today)
            ->whereNull('sent_at')
            ->limit(200)
            ->get();

        $sentCount = 0;

        foreach ($reminders as $reminder) {
            $medicine = $reminder->medicine;

            if (!$medicine || !$medicine->expiry_date) {
                $reminder->delete();
                continue;
            }

            $recipients = $this->buildRecipients($reminder);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new MedicineExpiryReminderNotification($medicine));
                $sentCount += $recipients->count();
            }

            $reminder->update(['sent_at' => now()]);
        }

        $this->info("Sent {$sentCount} medicine expiry reminders.");

        return Command::SUCCESS;
    }

    /**
     * Build recipients list - all family members (no role restrictions).
     */
    private function buildRecipients(MedicineExpiryReminder $reminder): Collection
    {
        $users = collect();
        $family = $reminder->medicine?->family;

        if (!$family) {
            return collect();
        }

        // Get all family members (roles and direct members)
        $family->roles()->with('user')->get()->each(function ($role) use ($users, $reminder) {
            if ($role->user && $role->user->email && $role->user->tenant_id === $reminder->medicine->tenant_id) {
                $users->push($role->user);
            }
        });

        $family->members()->with('user')->get()->each(function ($member) use ($users, $reminder) {
            if ($member->user && $member->user->email && $member->user->tenant_id === $reminder->medicine->tenant_id) {
                $users->push($member->user);
            }
        });

        return $users->unique('id');
    }
}






