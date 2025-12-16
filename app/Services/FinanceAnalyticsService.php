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

    /**
     * Get savings trend data (Income - Expenses) for a year.
     */
    public function getSavingsTrend(int $familyId, int $year): array
    {
        $monthlyData = $this->getMonthlyExpenses($familyId, $year);
        
        return array_map(function ($month) {
            return [
                'month' => $month['month'],
                'month_name' => $month['month_name'],
                'savings' => (float) ($month['income'] - $month['expenses']),
            ];
        }, $monthlyData);
    }

    /**
     * Get account balance trends over time.
     */
    public function getAccountBalanceTrends(int $familyId, int $year): array
    {
        $accounts = FinanceAccount::where('family_id', $familyId)
            ->where('is_active', true)
            ->get();

        $trends = [];

        foreach ($accounts as $account) {
            $accountTrend = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'balances' => []
            ];

            // Calculate balance at end of each month
            $runningBalance = (float) $account->initial_balance;
            
            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();

                // Get transactions for this account in this month
                $transactions = Transaction::where('family_id', $familyId)
                    ->where('finance_account_id', $account->id)
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->get();

                foreach ($transactions as $transaction) {
                    if ($transaction->type === 'INCOME') {
                        $runningBalance += $transaction->amount;
                    } elseif ($transaction->type === 'EXPENSE') {
                        $runningBalance -= $transaction->amount;
                    } elseif ($transaction->type === 'TRANSFER') {
                        if ($transaction->transfer_to_account_id === $account->id) {
                            // Money coming into this account
                            $runningBalance += $transaction->amount;
                        } else {
                            // Money going out of this account
                            $runningBalance -= $transaction->amount;
                        }
                    }
                }

                $accountTrend['balances'][] = [
                    'month' => $month,
                    'month_name' => $startDate->format('M'),
                    'balance' => $runningBalance,
                ];
            }

            $trends[] = $accountTrend;
        }

        return $trends;
    }

    /**
     * Get income sources breakdown for current month.
     */
    public function getIncomeSources(int $familyId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $transactions = Transaction::where('family_id', $familyId)
            ->where('type', 'INCOME')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNotNull('category_id')
            ->with('category')
            ->get();

        $incomeSources = [];

        foreach ($transactions as $transaction) {
            $categoryId = $transaction->category_id;
            $categoryName = $transaction->category ? $transaction->category->name : 'Other';

            if (!isset($incomeSources[$categoryId])) {
                $incomeSources[$categoryId] = [
                    'source_id' => $categoryId,
                    'source_name' => $categoryName,
                    'amount' => 0,
                ];
            }

            $incomeSources[$categoryId]['amount'] += $transaction->amount;
        }

        // If no category, group by description or use "Other"
        $uncategorized = Transaction::where('family_id', $familyId)
            ->where('type', 'INCOME')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNull('category_id')
            ->sum('amount');

        if ($uncategorized > 0) {
            $incomeSources['other'] = [
                'source_id' => 'other',
                'source_name' => 'Other',
                'amount' => (float) $uncategorized,
            ];
        }

        return array_values($incomeSources);
    }

    /**
     * Get expense patterns by day of week for current month.
     */
    public function getExpensePatternsByDay(int $familyId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $transactions = Transaction::where('family_id', $familyId)
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $dayPatterns = [
            'Monday' => 0,
            'Tuesday' => 0,
            'Wednesday' => 0,
            'Thursday' => 0,
            'Friday' => 0,
            'Saturday' => 0,
            'Sunday' => 0,
        ];

        foreach ($transactions as $transaction) {
            $dayName = Carbon::parse($transaction->transaction_date)->format('l');
            $dayPatterns[$dayName] += $transaction->amount;
        }

        return array_map(function ($day, $amount) {
            return [
                'day' => $day,
                'day_short' => substr($day, 0, 3),
                'amount' => (float) $amount,
            ];
        }, array_keys($dayPatterns), array_values($dayPatterns));
    }
}
