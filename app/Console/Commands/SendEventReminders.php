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

    public function handle(): void
    {
        $now = Carbon::now();

        $events = CalendarEvent::whereNotNull('reminder_before_minutes')
            ->whereNull('reminder_sent_at')
            ->where('start_at', '>=', $now)
            ->whereRaw('TIMESTAMPDIFF(MINUTE, ?, start_at) <= reminder_before_minutes', [$now])
            ->limit(200)
            ->get();

        foreach ($events as $event) {
            $family = Family::find($event->family_id);
            if (!$family) {
                continue;
            }

            $users = $family->members()->with('user')->get()->pluck('user')
                ->merge($family->roles()->with('user')->get()->pluck('user'))
                ->filter(fn ($u) => $u instanceof User)
                ->unique('id');

            if ($users->isNotEmpty()) {
                Notification::send($users, new EventReminder($event));
            }

            $event->update(['reminder_sent_at' => $now]);
        }
    }
}

