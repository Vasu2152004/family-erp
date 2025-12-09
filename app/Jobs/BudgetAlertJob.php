<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\BudgetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BudgetAlertJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(BudgetService $budgetService): void
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Get all active families
        $families = \App\Models\Family::all();

        foreach ($families as $family) {
            try {
                $budgetService->checkBudgetAlerts($family->id, $currentMonth, $currentYear);
            } catch (\Exception $e) {
                Log::error("Budget alert check failed for family {$family->id}: " . $e->getMessage());
            }
        }
    }
}
