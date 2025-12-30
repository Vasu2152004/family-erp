<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\TimezoneService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule budget alert job to run daily
Schedule::job(new \App\Jobs\BudgetAlertJob)->daily();

// Schedule low stock check to run daily at 9 AM IST (03:30 UTC)
// IST is UTC+5:30, so 09:00 IST = 03:30 UTC
Schedule::command('inventory:check-low-stock')->dailyAt(TimezoneService::convertScheduledTimeIstToUtc('09:00'));

// Send calendar event reminders every 5 minutes
Schedule::command('calendar:send-reminders')->everyFiveMinutes();

// Document expiry reminders daily at 8 AM IST (02:30 UTC)
// IST is UTC+5:30, so 08:00 IST = 02:30 UTC
Schedule::command('documents:send-expiry-reminders')->dailyAt(TimezoneService::convertScheduledTimeIstToUtc('08:00'));
// Vehicle expiry reminders daily at 8 AM IST (02:30 UTC)
Schedule::command('vehicles:send-expiry-reminders')->dailyAt(TimezoneService::convertScheduledTimeIstToUtc('08:00'));
// Medicine expiry reminders daily at 8 AM IST (02:30 UTC)
Schedule::command('medicines:send-expiry-reminders')->dailyAt(TimezoneService::convertScheduledTimeIstToUtc('08:00'));
// Medicine intake reminders every 5 minutes
Schedule::command('medicines:send-intake-reminders')->everyFiveMinutes();
// Medicine reminders every 5 minutes (health module)
Schedule::command('health:send-medicine-reminders')->everyFiveMinutes();
// Doctor visit reminders daily at 8 AM IST (02:30 UTC)
// IST is UTC+5:30, so 08:00 IST = 02:30 UTC
Schedule::command('health:send-doctor-visit-reminders')->dailyAt(TimezoneService::convertScheduledTimeIstToUtc('08:00'));

