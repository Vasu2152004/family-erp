<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule budget alert job to run daily
Schedule::job(new \App\Jobs\BudgetAlertJob)->daily();

// Schedule low stock check to run daily at 9 AM
Schedule::command('inventory:check-low-stock')->dailyAt('09:00');

// Send calendar event reminders every 5 minutes
Schedule::command('calendar:send-reminders')->everyFiveMinutes();
