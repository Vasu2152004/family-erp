<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

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
        $spent = $budget->getSpentAmount();
        $remaining = $budget->getRemainingAmount();
        $percentage = $budget->amount > 0 ? ($spent / $budget->amount) * 100 : 0;

        return [
            'spent' => $spent,
            'remaining' => $remaining,
            'percentage' => round($percentage, 2),
            'is_exceeded' => $budget->isExceeded(),
        ];
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
