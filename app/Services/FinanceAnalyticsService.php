<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\FinanceAccount;
use App\Models\FamilyMember;
use Carbon\Carbon;

class FinanceAnalyticsService
{
    /**
     * Get monthly expenses for a year.
     */
    public function getMonthlyExpenses(int $familyId, int $year): array
    {
        $data = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $expenses = Transaction::where('family_id', $familyId)
                ->where('type', 'EXPENSE')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount');

            $income = Transaction::where('family_id', $familyId)
                ->where('type', 'INCOME')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount');

            $data[] = [
                'month' => $month,
                'month_name' => $startDate->format('M'),
                'expenses' => (float) $expenses,
                'income' => (float) $income,
            ];
        }

        return $data;
    }

    /**
     * Get member-wise spending for a month.
     */
    public function getMemberWiseSpending(int $familyId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $transactions = Transaction::where('family_id', $familyId)
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNotNull('family_member_id')
            ->with('familyMember')
            ->get();

        $memberSpending = [];

        foreach ($transactions as $transaction) {
            $memberId = $transaction->family_member_id;
            $memberName = $transaction->familyMember 
                ? $transaction->familyMember->first_name . ' ' . $transaction->familyMember->last_name
                : 'Unknown';

            if (!isset($memberSpending[$memberId])) {
                $memberSpending[$memberId] = [
                    'member_id' => $memberId,
                    'member_name' => $memberName,
                    'amount' => 0,
                ];
            }

            $memberSpending[$memberId]['amount'] += $transaction->amount;
        }

        return array_values($memberSpending);
    }

    /**
     * Get category-wise expenses for a month.
     */
    public function getCategoryWiseExpenses(int $familyId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $transactions = Transaction::where('family_id', $familyId)
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNotNull('category_id')
            ->with('category')
            ->get();

        $categorySpending = [];

        foreach ($transactions as $transaction) {
            $categoryId = $transaction->category_id;
            $categoryName = $transaction->category ? $transaction->category->name : 'Uncategorized';

            if (!isset($categorySpending[$categoryId])) {
                $categorySpending[$categoryId] = [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                    'amount' => 0,
                ];
            }

            $categorySpending[$categoryId]['amount'] += $transaction->amount;
        }

        return array_values($categorySpending);
    }

    /**
     * Get all account balances for a family.
     */
    public function getAccountBalances(int $familyId): array
    {
        $accounts = FinanceAccount::where('family_id', $familyId)
            ->where('is_active', true)
            ->get();

        $balances = [];

        foreach ($accounts as $account) {
            $balances[] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'account_type' => $account->type,
                'balance' => (float) $account->current_balance,
            ];
        }

        return $balances;
    }
}
