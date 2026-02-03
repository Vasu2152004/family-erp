<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use Illuminate\Support\Collection;

class PerformanceHelper
{
    /**
     * Get accessible families for a user (request-level cached).
     * Optimized for serverless: minimal columns, indexed queries.
     */
    public static function getAccessibleFamilies(?int $userId): Collection
    {
        $key = 'accessible_families_' . ($userId ?? 'guest');
        if (!app()->bound($key)) {
            if ($userId === null) {
                app()->instance($key, collect());
            } else {
                $familyIds = FamilyUserRole::where('user_id', $userId)
                    ->select('family_id')
                    ->pluck('family_id')
                    ->merge(
                        FamilyMember::where('user_id', $userId)
                            ->select('family_id')
                            ->pluck('family_id')
                    )
                    ->unique()
                    ->values();
                app()->instance($key, Family::whereIn('id', $familyIds)
                    ->select(['id', 'name', 'tenant_id'])
                    ->orderBy('name')
                    ->get());
            }
        }

        return app($key);
    }
}
