<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FuelEntry;
use Carbon\Carbon;

class VehicleAnalyticsService
{
    /**
     * Get fuel consumption trends for a family.
     */
    public function getFuelConsumptionTrends(int $familyId, int $months = 12): array
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $fuelEntries = FuelEntry::where('family_id', $familyId)
            ->whereBetween('fill_date', [$startDate, $endDate])
            ->orderBy('fill_date')
            ->get();

        // Initialize data array for all months
        $monthlyData = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthlyData[$date->format('Y-m')] = [
                'month' => $date->month,
                'month_name' => $date->format('M'),
                'fuel_amount' => 0.0,
                'total_cost' => 0.0,
            ];
        }

        // Aggregate fuel entries by month
        foreach ($fuelEntries as $entry) {
            $monthKey = Carbon::parse($entry->fill_date)->format('Y-m');
            if (isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey]['fuel_amount'] += (float) $entry->fuel_amount;
                $monthlyData[$monthKey]['total_cost'] += (float) $entry->cost;
            }
        }

        return array_values($monthlyData);
    }
}
