<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Budget;

class BudgetAnalyticsService
{
    /**
     * Get budget vs actual spending for a family, month, and year.
     */
    public function getBudgetVsActual(int $familyId, int $month, int $year): array
    {
        $budgets = Budget::where('family_id', $familyId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_active', true)
            ->with('category')
            ->get();

        $data = [];

        foreach ($budgets as $budget) {
            $spentAmount = $budget->getSpentAmount();

            $data[] = [
                'budget_id' => $budget->id,
                'category_name' => $budget->category ? $budget->category->name : 'General',
                'budgeted_amount' => (float) $budget->amount,
                'spent_amount' => $spentAmount,
            ];
        }

        return $data;
    }
}
