<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FamilyUserRole;
use App\Models\MedicineReminder;
use App\Notifications\MedicineReminderNotification;
use App\Services\HealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendMedicineReminders extends Command
{
    protected $signature = 'health:send-medicine-reminders';

    protected $description = 'Send due medicine reminders to family members and admins';

    public function __construct(private readonly HealthService $healthService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();
        $dueReminders = MedicineReminder::with(['prescription', 'familyMember.user'])
            ->active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->limit(200)
            ->get();

        $sent = 0;

        foreach ($dueReminders as $reminder) {
            $prescription = $reminder->prescription;

            if (!$prescription) {
                $reminder->update(['status' => 'completed', 'next_run_at' => null]);
                continue;
            }

            $recipients = $this->buildRecipients($reminder);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new MedicineReminderNotification($prescription, $reminder));
                $sent += $recipients->count();
            }

            $nextRun = $this->healthService->calculateNextRunAt(
                $reminder->frequency,
                $reminder->reminder_time ?? '08:00',
                $reminder->start_date->toDateString(),
                $reminder->end_date?->toDateString(),
                $reminder->days_of_week ?? []
            );

            $reminder->update([
                'last_sent_at' => $now,
                'next_run_at' => $nextRun,
                'status' => $nextRun ? $reminder->status : 'completed',
            ]);
        }

        $this->info("Sent {$sent} medicine reminders.");

        return Command::SUCCESS;
    }

    private function buildRecipients(MedicineReminder $reminder): Collection
    {
        $users = collect();

        $roles = FamilyUserRole::where('family_id', $reminder->family_id)
            ->whereIn('role', ['OWNER', 'ADMIN'])
            ->with('user')
            ->get();

        foreach ($roles as $role) {
            if ($role->user && $role->user->tenant_id === $reminder->tenant_id) {
                $users->push($role->user);
            }
        }

        $memberUser = $reminder->familyMember?->user;
        if ($memberUser && $memberUser->tenant_id === $reminder->tenant_id) {
            $users->push($memberUser);
        }

        return $users->unique('id');
    }
}

