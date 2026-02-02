<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BudgetService
{
    /**
     * Create or update a budget.
     */
    public function createOrUpdateBudget(array $data, int $tenantId, int $familyId): Budget
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            $budget = Budget::updateOrCreate(
                [
                    'family_id' => $familyId,
                    'family_member_id' => $data['family_member_id'] ?? null,
                    'category_id' => $data['category_id'] ?? null,
                    'month' => (int) $data['month'],
                    'year' => (int) $data['year'],
                ],
                [
                    'tenant_id' => $tenantId,
                    'family_id' => $familyId,
                    'family_member_id' => $data['family_member_id'] ?? null,
                    'category_id' => $data['category_id'] ?? null,
                    'month' => (int) $data['month'],
                    'year' => (int) $data['year'],
                    'amount' => $data['amount'],
                    'alert_threshold' => $data['alert_threshold'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]
            );

            // Check for alerts after creating/updating
            $this->checkBudgetAlerts($familyId, (int) $data['month'], (int) $data['year']);

            return $budget->fresh();
        });
    }

    /**
     * Check budget alerts and send notifications if needed.
     */
    public function checkBudgetAlerts(int $familyId, int $month, int $year): void
    {
        $budgets = Budget::where('family_id', $familyId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_active', true)
            ->with(['familyMember.user', 'category', 'family'])
            ->get();

        foreach ($budgets as $budget) {
            // Refresh the budget to get latest spent amount
            $budget->refresh();
            
            if ($budget->isExceeded()) {
                // Send exceeded notification (check this first as it's more urgent)
                $this->sendBudgetExceededAlert($budget);
            } elseif ($budget->hasReachedAlertThreshold()) {
                // Send notification to admins/owners
                $this->sendBudgetAlert($budget);
            }
        }
    }

    /**
     * Get budget status (spent, remaining, percentage).
     */
    public function getBudgetStatus(int $budgetId): array
    {
        $budget = Budget::findOrFail($budgetId);

        return $this->getBudgetStatusFromModel($budget);
    }

    /**
     * Get budget status from an already-loaded Budget model (no extra fetch).
     */
    public function getBudgetStatusFromModel(Budget $budget): array
    {
        $spent = $budget->getSpentAmount();
        $remaining = max(0, (float) $budget->amount - $spent);
        $percentage = $budget->amount > 0 ? ($spent / (float) $budget->amount) * 100 : 0;

        return [
            'spent' => $spent,
            'remaining' => $remaining,
            'percentage' => round($percentage, 2),
            'is_exceeded' => $spent > (float) $budget->amount,
        ];
    }

    /**
     * Get budget status for multiple budgets in one query (avoids N+1).
     * Uses budget_id on transactions; all budgets must share same month/year.
     */
    public function getBudgetStatusForBudgets(Collection $budgets): array
    {
        if ($budgets->isEmpty()) {
            return [];
        }

        $first = $budgets->first();
        $year = (int) $first->year;
        $month = (int) $first->month;
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $budgetIds = $budgets->pluck('id')->all();

        if (!Schema::hasColumn((new Transaction)->getTable(), 'budget_id')) {
            $result = [];
            foreach ($budgets as $budget) {
                $result[$budget->id] = $this->getBudgetStatusFromModel($budget);
            }
            return $result;
        }

        $spentByBudget = Transaction::whereIn('budget_id', $budgetIds)
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('budget_id')
            ->selectRaw('budget_id, sum(amount) as spent')
            ->pluck('spent', 'budget_id')
            ->all();

        $result = [];
        foreach ($budgets as $budget) {
            $spent = (float) ($spentByBudget[$budget->id] ?? 0);
            $amount = (float) $budget->amount;
            $remaining = max(0, $amount - $spent);
            $percentage = $amount > 0 ? ($spent / $amount) * 100 : 0;
            $result[$budget->id] = [
                'spent' => $spent,
                'remaining' => $remaining,
                'percentage' => round($percentage, 2),
                'is_exceeded' => $spent > $amount,
            ];
        }

        return $result;
    }

    /**
     * Send budget alert notification.
     */
    private function sendBudgetAlert(Budget $budget): void
    {
        $family = $budget->family;
        $categoryName = $budget->category?->name ?? 'Total';
        $budgetType = $budget->isPersonal() 
            ? ($budget->familyMember?->user?->name ?? 'Personal') . ' Personal Budget'
            : 'Family Budget';
        
        $usersToNotify = collect();
        
        // Always notify admins and owners
        $adminsAndOwners = $family->roles()
            ->whereIn('role', ['OWNER', 'ADMIN'])
            ->get();
        $usersToNotify = $usersToNotify->merge($adminsAndOwners->pluck('user_id'));
        
        // If personal budget, also notify the member
        if ($budget->isPersonal() && $budget->familyMember) {
            $usersToNotify->push($budget->familyMember->user_id);
        }
        
        foreach ($usersToNotify->unique() as $userId) {
            // Check if notification already exists today for this budget and user
            $existingNotification = \App\Models\Notification::where('user_id', $userId)
                ->where('type', 'budget_alert')
                ->where('data->budget_id', $budget->id)
                ->whereDate('created_at', today())
                ->first();

            if (!$existingNotification) {
                \App\Models\Notification::create([
                    'tenant_id' => $family->tenant_id,
                    'user_id' => $userId,
                    'type' => 'budget_alert',
                    'title' => 'Budget Alert',
                    'message' => "⚠️ {$budgetType} for {$categoryName} has reached {$budget->alert_threshold}% threshold for " . \Carbon\Carbon::create($budget->year, $budget->month, 1)->format('F Y') . ". Current spending: " . number_format((float) $budget->getSpentAmount(), 2) . " of " . number_format((float) $budget->amount, 2) . ".",
                    'data' => [
                        'family_id' => $family->id,
                        'budget_id' => $budget->id,
                        'category_id' => $budget->category_id,
                        'month' => $budget->month,
                        'year' => $budget->year,
                    ],
                ]);
            }
        }
    }

    /**
     * Send budget exceeded notification.
     */
    private function sendBudgetExceededAlert(Budget $budget): void
    {
        $family = $budget->family;
        $categoryName = $budget->category?->name ?? 'Total';
        $budgetType = $budget->isPersonal() 
            ? ($budget->familyMember?->user?->name ?? 'Personal') . ' Personal Budget'
            : 'Family Budget';
        
        $usersToNotify = collect();
        
        // Always notify admins and owners
        $adminsAndOwners = $family->roles()
            ->whereIn('role', ['OWNER', 'ADMIN'])
            ->get();
        $usersToNotify = $usersToNotify->merge($adminsAndOwners->pluck('user_id'));
        
        // If personal budget, also notify the member
        if ($budget->isPersonal() && $budget->familyMember) {
            $usersToNotify->push($budget->familyMember->user_id);
        }
        
        foreach ($usersToNotify->unique() as $userId) {
            // Check if notification already exists today for this budget and user
            $existingNotification = \App\Models\Notification::where('user_id', $userId)
                ->where('type', 'budget_exceeded')
                ->where('data->budget_id', $budget->id)
                ->whereDate('created_at', today())
                ->first();

            if (!$existingNotification) {
                \App\Models\Notification::create([
                    'tenant_id' => $family->tenant_id,
                    'user_id' => $userId,
                    'type' => 'budget_exceeded',
                    'title' => 'Budget Exceeded',
                    'message' => "🚨 {$budgetType} for {$categoryName} has been EXCEEDED for " . \Carbon\Carbon::create($budget->year, $budget->month, 1)->format('F Y') . "! Current spending: " . number_format((float) $budget->getSpentAmount(), 2) . " of " . number_format((float) $budget->amount, 2) . ".",
                    'data' => [
                        'family_id' => $family->id,
                        'budget_id' => $budget->id,
                        'category_id' => $budget->category_id,
                        'month' => $budget->month,
                        'year' => $budget->year,
                    ],
                ]);
            }
        }
    }
}
