<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MedicineIntakeReminder;
use App\Notifications\MedicineIntakeReminder as MedicineIntakeReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendMedicineIntakeReminders extends Command
{
    protected $signature = 'medicines:send-intake-reminders';

    protected $description = 'Send reminders for medicine intake times';

    public function handle(): int
    {
        $now = Carbon::now();
        $reminders = MedicineIntakeReminder::with(['medicine.family.roles.user', 'medicine.family.members.user', 'familyMember.user'])
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->limit(200)
            ->get();

        $sentCount = 0;

        foreach ($reminders as $reminder) {
            $medicine = $reminder->medicine;

            if (!$medicine) {
                $reminder->delete();
                continue;
            }

            // Check if reminder has ended
            if ($reminder->end_date && Carbon::parse($reminder->end_date)->isPast()) {
                $reminder->update(['status' => 'completed']);
                continue;
            }

            // Check if reminder has started
            if ($reminder->start_date && Carbon::parse($reminder->start_date)->isFuture()) {
                continue;
            }

            // For custom dates, check if today is in selected_dates
            if ($reminder->frequency === 'custom' && !empty($reminder->selected_dates)) {
                $today = $now->toDateString();
                if (!in_array($today, $reminder->selected_dates)) {
                    // Calculate next run for custom dates
                    $nextRunAt = $reminder->calculateNextRunAt();
                    $reminder->update(['next_run_at' => $nextRunAt]);
                    continue;
                }
            }

            $recipients = $this->buildRecipients($reminder);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new MedicineIntakeReminderNotification($medicine, $reminder));
                $sentCount += $recipients->count();
            }

            // Update last_sent_at and calculate next_run_at
            $nextRunAt = $reminder->calculateNextRunAt();
            $reminder->update([
                'last_sent_at' => $now,
                'next_run_at' => $nextRunAt,
            ]);

            // If no next run (custom dates exhausted), mark as completed
            if (!$nextRunAt && $reminder->frequency === 'custom') {
                $reminder->update(['status' => 'completed']);
            }
        }

        $this->info("Sent {$sentCount} medicine intake reminders.");

        return Command::SUCCESS;
    }

    /**
     * Build recipients list - all family members (no role restrictions).
     */
    private function buildRecipients(MedicineIntakeReminder $reminder): Collection
    {
        $users = collect();
        $family = $reminder->medicine?->family;

        if (!$family) {
            return collect();
        }

        // If reminder is for specific family member, send to that member's user
        if ($reminder->family_member_id && $reminder->familyMember?->user) {
            $users->push($reminder->familyMember->user);
        } else {
            // Otherwise, send to all family members
            $family->roles()->with('user')->get()->each(function ($role) use ($users, $reminder) {
                if ($role->user && $role->user->tenant_id === $reminder->medicine->tenant_id) {
                    $users->push($role->user);
                }
            });

            $family->members()->with('user')->get()->each(function ($member) use ($users, $reminder) {
                if ($member->user && $member->user->tenant_id === $reminder->medicine->tenant_id) {
                    $users->push($member->user);
                }
            });
        }

        return $users->unique('id');
    }
}
