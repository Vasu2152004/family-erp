<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Investment;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;

class InvestmentAnalyticsService
{
    /**
     * Get investment type distribution (excluding hidden investments).
     */
    public function getInvestmentTypeDistribution(int $familyId): array
    {
        $investments = Investment::where('family_id', $familyId)
            ->where('is_hidden', false) // CRITICAL: Exclude hidden investments
            ->select('investment_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(current_value, amount)) as total_value'))
            ->groupBy('investment_type')
            ->get();

        return $investments->map(function ($item) {
            return [
                'type' => str_replace('_', ' ', $item->investment_type),
                'count' => (int) $item->count,
                'total_value' => (float) $item->total_value,
            ];
        })->toArray();
    }

    /**
     * Get investment value trend over time (excluding hidden investments).
     */
    public function getInvestmentValueTrend(int $familyId): array
    {
        $investments = Investment::where('family_id', $familyId)
            ->where('is_hidden', false) // CRITICAL: Exclude hidden investments
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(COALESCE(current_value, amount)) as total_value'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $investments->map(function ($item) {
            return [
                'month' => $item->month,
                'total_value' => (float) $item->total_value,
                'count' => (int) $item->count,
            ];
        })->toArray();
    }

    /**
     * Calculate investment value at a specific point in time.
     */
    private function calculateValueAtDate(
        Investment $investment,
        \Carbon\Carbon $targetDate
    ): float {
        // Safety check: if investment is null, return 0
        if (!$investment) {
            return 0.0;
        }

        // If no start_date or interest_rate, use current_value or amount
        if (!$investment->start_date || !$investment->interest_rate) {
            return (float) ($investment->current_value ?? $investment->amount ?? 0);
        }

        $start = \Carbon\Carbon::parse($investment->start_date);
        
        // If target date is before start date, return amount
        if ($targetDate->lt($start)) {
            return (float) $investment->amount;
        }

        $years = $start->diffInYears($targetDate, true);
        $months = $start->diffInMonths($targetDate, true);
        $amount = (float) $investment->amount;
        $interestRate = (float) $investment->interest_rate;
        $interestPeriod = $investment->interest_period;
        $monthlyPremium = $investment->monthly_premium ? (float) $investment->monthly_premium : null;
        $investmentType = $investment->investment_type;

        switch ($investmentType) {
            case 'FD': // Fixed Deposit - compound interest
                $n = 1; // Compounding frequency (yearly)
                $t = $years;
                $r = $interestRate / 100;
                return $amount * pow(1 + ($r / $n), $n * $t);

            case 'RD': // Recurring Deposit
                if ($monthlyPremium) {
                    $monthlyRate = $interestRate / 12 / 100;
                    $n = (int) $months;
                    if ($n > 0) {
                        return $monthlyPremium * ((pow(1 + $monthlyRate, $n) - 1) / $monthlyRate);
                    }
                }
                return $amount;

            case 'SIP': // Systematic Investment Plan
                // SIP only uses monthly premium and interest rate, NOT amount
                if ($monthlyPremium && $interestRate) {
                    $monthlyRate = $interestRate / 12 / 100;
                    $n = (int) $months;
                    if ($n > 0) {
                        return $monthlyPremium * ((pow(1 + $monthlyRate, $n) - 1) / $monthlyRate);
                    }
                }
                // If no monthly premium or interest rate, return 0
                return 0;

            default:
                // For other types, use simple interest based on interest_period
                if ($interestPeriod === 'YEARLY') {
                    return $amount * (1 + ($interestRate / 100) * $years);
                } elseif ($interestPeriod === 'MONTHLY') {
                    return $amount * (1 + ($interestRate / 12 / 100) * $months);
                } elseif ($interestPeriod === 'QUARTERLY') {
                    $quarters = $months / 3;
                    return $amount * (1 + ($interestRate / 4 / 100) * $quarters);
                }
                // Default to simple yearly interest
                return $amount * (1 + ($interestRate / 100) * $years);
        }
    }

    /**
     * Get profit/loss trend over time (excluding hidden investments).
     * Uses start_date if available, otherwise falls back to created_at.
     * Returns cumulative profit/loss over time - showing how total profit/loss changes month by month.
     * Calculates the value of each investment at each specific month based on interest calculations.
     */
    public function getProfitLossTrend(int $familyId): array
    {
        $investments = Investment::where('family_id', $familyId)
            ->where('is_hidden', false) // CRITICAL: Exclude hidden investments
            ->get();

        if ($investments->isEmpty()) {
            return [];
        }

        // Find earliest and latest dates
        $earliestDate = null;
        foreach ($investments as $investment) {
            if (!$investment) {
                continue;
            }
            $dateField = $investment->start_date ?? $investment->created_at;
            if (!$dateField) {
                continue;
            }
            $date = \Carbon\Carbon::parse($dateField);
            if (!$earliestDate || $date->lt($earliestDate)) {
                $earliestDate = $date;
            }
        }

        $today = \Carbon\Carbon::now();
        $currentMonth = $today->format('Y-m');
        $earliestMonth = $earliestDate->format('Y-m');
        
        $start = \Carbon\Carbon::parse($earliestMonth . '-01')->startOfMonth();
        $end = \Carbon\Carbon::parse($currentMonth . '-01')->startOfMonth();
        
        $result = [];
        $current = $start->copy();
        
        while ($current->lte($end)) {
            $monthKey = $current->format('Y-m');
            $monthEnd = $current->copy()->endOfMonth();
            
            $cumulativeInvested = 0;
            $cumulativeCurrent = 0;
            
            // For each investment, calculate its value at this specific month
            foreach ($investments as $investment) {
                if (!$investment) {
                    continue;
                }
                $investmentStartDate = $investment->start_date 
                    ? \Carbon\Carbon::parse($investment->start_date)
                    : \Carbon\Carbon::parse($investment->created_at ?? now());
                
                // Only include investments that have started by this month
                if ($investmentStartDate->lte($monthEnd)) {
                    $cumulativeInvested += (float) $investment->amount;
                    // Calculate what the value would be at this specific month
                    $valueAtMonth = $this->calculateValueAtDate($investment, $monthEnd);
                    $cumulativeCurrent += $valueAtMonth;
                }
            }
            
            // Calculate profit/loss for this month
            $profitLoss = $cumulativeCurrent - $cumulativeInvested;
            
            $result[] = [
                'month' => $monthKey,
                'total_invested' => $cumulativeInvested,
                'total_current' => $cumulativeCurrent,
                'profit_loss' => $profitLoss,
            ];
            
            $current->addMonth();
        }
        
        return $result;
    }

    /**
     * Get owner-wise distribution (excluding hidden investments).
     */
    public function getOwnerWiseDistribution(int $familyId): array
    {
        $investments = Investment::where('family_id', $familyId)
            ->where('is_hidden', false) // CRITICAL: Exclude hidden investments
            ->with('familyMember', 'createdBy')
            ->get();

        $ownerData = [];

        foreach ($investments as $investment) {
            // If no family member assigned, it's a Family Investment
            if ($investment->familyMember) {
                $ownerName = trim($investment->familyMember->first_name . ' ' . $investment->familyMember->last_name);
            } else {
                $ownerName = 'Family Investment';
            }

            if (!isset($ownerData[$ownerName])) {
                $ownerData[$ownerName] = [
                    'owner_name' => $ownerName,
                    'count' => 0,
                    'total_value' => 0,
                ];
            }

            $ownerData[$ownerName]['count']++;
            $ownerData[$ownerName]['total_value'] += (float) ($investment->current_value ?? $investment->amount);
        }

        return array_values($ownerData);
    }

    /**
     * Get investment count by type (excluding hidden investments).
     */
    public function getInvestmentCountByType(int $familyId): array
    {
        $investments = Investment::where('family_id', $familyId)
            ->where('is_hidden', false) // CRITICAL: Exclude hidden investments
            ->select('investment_type', DB::raw('COUNT(*) as count'))
            ->groupBy('investment_type')
            ->get();

        return $investments->map(function ($item) {
            return [
                'type' => str_replace('_', ' ', $item->investment_type),
                'count' => (int) $item->count,
            ];
        })->toArray();
    }

}










