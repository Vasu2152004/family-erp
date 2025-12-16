<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryItem;

class InventoryAnalyticsService
{
    /**
     * Get category-wise distribution of inventory items.
     */
    public function getCategoryWiseDistribution(int $familyId): array
    {
        $items = InventoryItem::where('family_id', $familyId)
            ->with(['category', 'batches'])
            ->get();

        $categoryDistribution = [];

        foreach ($items as $item) {
            // Calculate total quantity (base qty + batches)
            $totalQty = (float) $item->qty;
            if ($item->relationLoaded('batches')) {
                $totalQty += (float) $item->batches->sum('qty');
            } else {
                $totalQty += (float) $item->batches()->sum('qty');
            }

            $categoryId = $item->category_id;
            $categoryName = $item->category ? $item->category->name : 'Uncategorized';

            if (!isset($categoryDistribution[$categoryId])) {
                $categoryDistribution[$categoryId] = [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                    'total_qty' => 0,
                ];
            }

            $categoryDistribution[$categoryId]['total_qty'] += $totalQty;
        }

        return array_values($categoryDistribution);
    }

    /**
     * Get stock status overview (Healthy, Low Stock, Out of Stock).
     */
    public function getStockStatusOverview(int $familyId): array
    {
        $items = InventoryItem::where('family_id', $familyId)
            ->with('batches')
            ->get();

        $statusCounts = [
            'healthy' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
        ];

        foreach ($items as $item) {
            // Calculate total quantity (base qty + batches)
            $totalQty = (float) $item->qty;
            if ($item->relationLoaded('batches')) {
                $totalQty += (float) $item->batches->sum('qty');
            } else {
                $totalQty += (float) $item->batches()->sum('qty');
            }

            // Determine status
            if ($totalQty == 0) {
                $statusCounts['out_of_stock']++;
            } elseif ($totalQty < (float) $item->min_qty) {
                $statusCounts['low_stock']++;
            } else {
                $statusCounts['healthy']++;
            }
        }

        return [
            [
                'status' => 'healthy',
                'label' => 'Healthy',
                'count' => $statusCounts['healthy'],
            ],
            [
                'status' => 'low_stock',
                'label' => 'Low Stock',
                'count' => $statusCounts['low_stock'],
            ],
            [
                'status' => 'out_of_stock',
                'label' => 'Out of Stock',
                'count' => $statusCounts['out_of_stock'],
            ],
        ];
    }
}
