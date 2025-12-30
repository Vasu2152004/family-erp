<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DoctorVisit;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Notifications\DoctorVisitReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDoctorVisitReminders extends Command
{
    protected $signature = 'health:send-doctor-visit-reminders';

    protected $description = 'Send email reminders for doctor visits scheduled for tomorrow';

    public function handle(): int
    {
        $now = Carbon::now();
        $tomorrow = $now->copy()->addDay()->startOfDay();
        $tomorrowEnd = $tomorrow->copy()->endOfDay();
        
        $this->info("Checking for doctor visits scheduled for tomorrow ({$tomorrow->format('Y-m-d')}) at {$now->format('Y-m-d H:i:s')} (UTC)...");

        // Get all doctor visits with next_visit_date = tomorrow that haven't been reminded yet
        $visits = DoctorVisit::whereNotNull('next_visit_date')
            ->whereDate('next_visit_date', $tomorrow->format('Y-m-d'))
            ->whereNull('reminder_sent_at')
            ->with(['familyMember', 'family'])
            ->limit(200)
            ->get();

        $this->info("Found {$visits->count()} visit(s) needing reminders.");

        if ($visits->isEmpty()) {
            $this->info("No reminders to send.");
            return Command::SUCCESS;
        }

        $sentCount = 0;
        $userCount = 0;

        foreach ($visits as $visit) {
            $family = $visit->family;
            if (!$family) {
                $this->warn("Family not found for visit ID {$visit->id}");
                continue;
            }

            // Collect users to notify:
            // 1. The family member (if they have a linked user account)
            // 2. Family admins and owners
            $users = collect();

            // Add the family member's linked user if exists
            if ($visit->family_member_id) {
                $member = $visit->familyMember;
                if ($member && $member->user_id) {
                    $memberUser = User::find($member->user_id);
                    if ($memberUser && $memberUser->email) {
                        $users->push($memberUser);
                    }
                }
            }

            // Add family admins and owners
            $adminsAndOwners = FamilyUserRole::where('family_id', $family->id)
                ->whereIn('role', ['OWNER', 'ADMIN'])
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter(fn ($u) => $u instanceof User && $u->email);

            $users = $users->merge($adminsAndOwners)->unique('id');

            if ($users->isEmpty()) {
                $memberName = $visit->familyMember 
                    ? $visit->familyMember->first_name . ' ' . $visit->familyMember->last_name
                    : 'Unknown';
                $this->warn("No users with email found for visit: {$memberName} - {$visit->doctor_name}");
                continue;
            }

            try {
                Notification::send($users, new DoctorVisitReminder($visit));
                $sentCount++;
                $userCount += $users->count();
                
                $memberName = $visit->familyMember 
                    ? $visit->familyMember->first_name . ' ' . $visit->familyMember->last_name
                    : 'Unknown';
                $this->info("✓ Sent reminder for '{$memberName}' - {$visit->doctor_name} to {$users->count()} user(s)");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for visit ID {$visit->id}: " . $e->getMessage());
                continue;
            }

            // Mark reminder as sent
            $visit->update(['reminder_sent_at' => $now]);
        }

        $this->info("Completed. Sent {$sentCount} reminder(s) to {$userCount} user(s) total.");
        return Command::SUCCESS;
    }
}


