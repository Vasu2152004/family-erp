<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Helpers\PerformanceHelper;
use App\Models\Family;
use Illuminate\Support\Facades\Auth;

trait HasFamilyContext
{
    /**
     * Get all families accessible to the current user.
     */
    protected function getAccessibleFamilies()
    {
        $user = once(fn () => Auth::user());

        return PerformanceHelper::getAccessibleFamilies($user?->id);
    }

    /**
     * Get the active family from session or request, or return the first accessible family.
     */
    protected function getActiveFamily(mixed $familyId = null): ?Family
    {
        $families = $this->getAccessibleFamilies();
        if ($families->isEmpty()) {
            return null;
        }

        // If family ID is provided in request, use it and store in session
        if ($familyId) {
            $familyId = is_numeric($familyId) ? (int) $familyId : null;
            if ($familyId) {
                $family = $families->firstWhere('id', $familyId);
                if ($family) {
                    session(['active_finance_family_id' => $family->id]);
                    return $family;
                }
            }
        }

        // Try to get from session
        $sessionFamilyId = session('active_finance_family_id');
        if ($sessionFamilyId) {
            $family = $families->firstWhere('id', $sessionFamilyId);
            if ($family) {
                return $family;
            }
        }

        // Fallback to first accessible family
        $firstFamily = $families->first();
        session(['active_finance_family_id' => $firstFamily->id]);

        return $firstFamily;
    }

    /**
     * Check if user can access the given family.
     */
    protected function canAccessFamily(Family $family): bool
    {
        $accessibleFamilies = $this->getAccessibleFamilies();

        return $accessibleFamilies->contains('id', $family->id);
    }
}

