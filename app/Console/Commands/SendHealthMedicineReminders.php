<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MedicineReminder;
use App\Notifications\HealthMedicineReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendHealthMedicineReminders extends Command
{
    protected $signature = 'health:send-medicine-reminders';

    protected $description = 'Send reminders for medicine intake times from health module prescriptions';

    public function handle(): int
    {
        $now = Carbon::now();
        $reminders = MedicineReminder::with(['prescription.family.roles.user', 'prescription.family.members.user', 'familyMember.user'])
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->limit(200)
            ->get();

        $sentCount = 0;

        foreach ($reminders as $reminder) {
            $prescription = $reminder->prescription;

            if (!$prescription) {
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

            $recipients = $this->buildRecipients($reminder);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new HealthMedicineReminder($prescription, $reminder));
                $sentCount += $recipients->count();
            }

            // Update last_sent_at and calculate next_run_at
            $nextRunAt = $reminder->calculateNextRunAt();
            $reminder->update([
                'last_sent_at' => $now,
                'next_run_at' => $nextRunAt,
            ]);

            // If no next run, mark as completed
            if (!$nextRunAt) {
                $reminder->update(['status' => 'completed']);
            }
        }

        $this->info("Sent {$sentCount} health medicine reminders.");

        return Command::SUCCESS;
    }

    /**
     * Build recipients list - all family members (no role restrictions).
     */
    private function buildRecipients(MedicineReminder $reminder): Collection
    {
        $users = collect();
        $family = $reminder->prescription?->family;

        if (!$family) {
            return collect();
        }

        // If reminder is for specific family member, send to that member's user
        if ($reminder->family_member_id && $reminder->familyMember?->user) {
            $user = $reminder->familyMember->user;
            if ($user->email && $user->tenant_id === $reminder->prescription->tenant_id) {
                $users->push($user);
            }
        } else {
            // Otherwise, send to all family members
            $family->roles()->with('user')->get()->each(function ($role) use ($users, $reminder) {
                if ($role->user && $role->user->email && $role->user->tenant_id === $reminder->prescription->tenant_id) {
                    $users->push($role->user);
                }
            });

            $family->members()->with('user')->get()->each(function ($member) use ($users, $reminder) {
                if ($member->user && $member->user->email && $member->user->tenant_id === $reminder->prescription->tenant_id) {
                    $users->push($member->user);
                }
            });
        }

        return $users->unique('id');
    }
}







