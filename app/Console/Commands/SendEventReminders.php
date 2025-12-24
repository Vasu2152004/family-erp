<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\Family;
use App\Models\User;
use App\Notifications\EventReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendEventReminders extends Command
{
    protected $signature = 'calendar:send-reminders';

    protected $description = 'Send reminders for upcoming calendar events';

    public function handle(): int
    {
        $now = Carbon::now();
        $this->info("Checking for event reminders at {$now->format('Y-m-d H:i:s')} (UTC)...");

        // Get all events with reminders that haven't been sent yet
        // Note: All times are in UTC (start_at is stored in UTC, Carbon::now() returns UTC)
        $events = CalendarEvent::whereNotNull('reminder_before_minutes')
            ->whereNull('reminder_sent_at')
            ->where('start_at', '>=', $now)
            ->limit(200)
            ->get()
            ->filter(function ($event) use ($now) {
                // Calculate when reminder should be sent (start_at - reminder_before_minutes)
                // Both start_at and reminderTime are in UTC, so calculation is correct
                $reminderTime = $event->start_at->copy()->subMinutes($event->reminder_before_minutes);
                // Send if current time is >= reminder time and < start time
                return $now->greaterThanOrEqualTo($reminderTime) && $now->lessThan($event->start_at);
            });

        $this->info("Found {$events->count()} event(s) needing reminders.");

        $sentCount = 0;
        $userCount = 0;

        foreach ($events as $event) {
            $family = Family::find($event->family_id);
            if (!$family) {
                $this->warn("Family not found for event ID {$event->id}");
                continue;
            }

            $users = $family->members()->with('user')->get()->pluck('user')
                ->merge($family->roles()->with('user')->get()->pluck('user'))
                ->filter(fn ($u) => $u instanceof User && $u->email)
                ->unique('id');

            if ($users->isEmpty()) {
                $this->warn("No users with email found for event: {$event->title}");
                continue;
            }

            try {
                Notification::send($users, new EventReminder($event));
                $sentCount++;
                $userCount += $users->count();
                $this->info("✓ Sent reminder for '{$event->title}' to {$users->count()} user(s)");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for '{$event->title}': " . $e->getMessage());
            }

            $event->update(['reminder_sent_at' => $now]);
        }

        $this->info("Completed. Sent {$sentCount} reminder(s) to {$userCount} user(s) total.");
        return Command::SUCCESS;
    }
}

