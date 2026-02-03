<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Family;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PerformanceHelper
{
    /**
     * Get accessible families for a user (request-level cached).
     * Single DB round-trip for family IDs, one for families. Optimized for serverless.
     */
    public static function getAccessibleFamilies(?int $userId): Collection
    {
        $key = 'accessible_families_' . ($userId ?? 'guest');
        if (!app()->bound($key)) {
            if ($userId === null) {
                app()->instance($key, collect());
            } else {
                $familyIds = collect(DB::select(
                    'SELECT family_id FROM family_user_roles WHERE user_id = ? UNION SELECT family_id FROM family_members WHERE user_id = ?',
                    [$userId, $userId]
                ))->pluck('family_id')->unique()->values()->all();

                if (empty($familyIds)) {
                    app()->instance($key, collect());
                } else {
                    app()->instance($key, Family::whereIn('id', $familyIds)
                        ->select(['id', 'name', 'tenant_id'])
                        ->orderBy('name')
                        ->get());
                }
            }
        }

        return app($key);
    }
}
