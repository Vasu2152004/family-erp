<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;

class AssetAnalyticsService
{
    /**
     * Get asset type distribution (excluding locked assets).
     */
    public function getAssetTypeDistribution(int $familyId): array
    {
        $assets = Asset::where('family_id', $familyId)
            ->where('is_locked', false) // CRITICAL: Exclude locked assets
            ->select('asset_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(current_value, purchase_value)) as total_value'))
            ->groupBy('asset_type')
            ->get();

        return $assets->map(function ($item) {
            return [
                'type' => str_replace('_', ' ', $item->asset_type),
                'count' => (int) $item->count,
                'total_value' => (float) $item->total_value,
            ];
        })->toArray();
    }

    /**
     * Get asset value trend over time (excluding locked assets).
     */
    public function getAssetValueTrend(int $familyId): array
    {
        $assets = Asset::where('family_id', $familyId)
            ->where('is_locked', false) // CRITICAL: Exclude locked assets
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(COALESCE(current_value, purchase_value)) as total_value'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $assets->map(function ($item) {
            return [
                'month' => $item->month,
                'total_value' => (float) $item->total_value,
                'count' => (int) $item->count,
            ];
        })->toArray();
    }

    /**
     * Get profit/loss trend over time (excluding locked assets).
     * Returns cumulative profit/loss over time - showing how total profit/loss changes month by month.
     * For assets, we use the current_value which represents today's value.
     */
    public function getProfitLossTrend(int $familyId): array
    {
        $assets = Asset::where('family_id', $familyId)
            ->where('is_locked', false) // CRITICAL: Exclude locked assets
            ->get();

        if ($assets->isEmpty()) {
            return [];
        }

        // Find earliest date
        $earliestDate = null;
        foreach ($assets as $asset) {
            $date = \Carbon\Carbon::parse($asset->created_at);
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
            
            $cumulativePurchased = 0;
            $cumulativeCurrent = 0;
            
            // For each asset, include it if it was created by this month
            foreach ($assets as $asset) {
                $assetCreatedDate = \Carbon\Carbon::parse($asset->created_at);
                
                // Only include assets that were created by this month
                if ($assetCreatedDate->lte($monthEnd)) {
                    $cumulativePurchased += (float) ($asset->purchase_value ?? 0);
                    // Use current_value (today's value) for all months
                    // This shows how the portfolio value changes as assets are added
                    $cumulativeCurrent += (float) ($asset->current_value ?? $asset->purchase_value ?? 0);
                }
            }
            
            // Calculate profit/loss for this month
            $profitLoss = $cumulativeCurrent - $cumulativePurchased;
            
            $result[] = [
                'month' => $monthKey,
                'total_purchased' => $cumulativePurchased,
                'total_current' => $cumulativeCurrent,
                'profit_loss' => $profitLoss,
            ];
            
            $current->addMonth();
        }
        
        return $result;
    }

    /**
     * Get owner-wise distribution (excluding locked assets).
     */
    public function getOwnerWiseDistribution(int $familyId): array
    {
        $assets = Asset::where('family_id', $familyId)
            ->where('is_locked', false) // CRITICAL: Exclude locked assets
            ->with('familyMember', 'createdBy')
            ->get();

        $ownerData = [];

        foreach ($assets as $asset) {
            $ownerName = 'Unassigned';
            
            if ($asset->familyMember) {
                $ownerName = trim($asset->familyMember->first_name . ' ' . $asset->familyMember->last_name);
            } elseif ($asset->createdBy) {
                $ownerName = $asset->createdBy->name;
            }

            if (!isset($ownerData[$ownerName])) {
                $ownerData[$ownerName] = [
                    'owner_name' => $ownerName,
                    'count' => 0,
                    'total_value' => 0,
                ];
            }

            $ownerData[$ownerName]['count']++;
            $ownerData[$ownerName]['total_value'] += (float) ($asset->current_value ?? $asset->purchase_value ?? 0);
        }

        return array_values($ownerData);
    }

    /**
     * Get asset count by type (excluding locked assets).
     */
    public function getAssetCountByType(int $familyId): array
    {
        $assets = Asset::where('family_id', $familyId)
            ->where('is_locked', false) // CRITICAL: Exclude locked assets
            ->select('asset_type', DB::raw('COUNT(*) as count'))
            ->groupBy('asset_type')
            ->get();

        return $assets->map(function ($item) {
            return [
                'type' => str_replace('_', ' ', $item->asset_type),
                'count' => (int) $item->count,
            ];
        })->toArray();
    }

}











