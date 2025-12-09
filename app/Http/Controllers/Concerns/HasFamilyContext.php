<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Family;
use Illuminate\Support\Facades\Auth;

trait HasFamilyContext
{
    /**
     * Get all families accessible to the current user.
     */
    protected function getAccessibleFamilies()
    {
        $user = Auth::user();
        
        $familyIdsFromRoles = \App\Models\FamilyUserRole::where('user_id', $user->id)
            ->pluck('family_id')
            ->unique();
        
        $familyIdsFromMembers = \App\Models\FamilyMember::where('user_id', $user->id)
            ->pluck('family_id')
            ->unique();
        
        $familyIds = $familyIdsFromRoles->merge($familyIdsFromMembers)->unique()->values();
        
        return Family::whereIn('id', $familyIds)->orderBy('name')->get();
    }

    /**
     * Get the active family from session or request, or return the first accessible family.
     */
    protected function getActiveFamily(mixed $familyId = null): ?Family
    {
        // If family ID is provided in request, use it and store in session
        if ($familyId) {
            // Cast to int if it's a string
            $familyId = is_numeric($familyId) ? (int) $familyId : null;
            if ($familyId) {
                $family = Family::find($familyId);
                if ($family && $this->canAccessFamily($family)) {
                    session(['active_finance_family_id' => $family->id]);
                    return $family;
                }
            }
        }

        // Try to get from session
        $sessionFamilyId = session('active_finance_family_id');
        if ($sessionFamilyId) {
            $family = Family::find($sessionFamilyId);
            if ($family && $this->canAccessFamily($family)) {
                return $family;
            }
        }

        // Fallback to first accessible family
        $families = $this->getAccessibleFamilies();
        if ($families->isNotEmpty()) {
            $firstFamily = $families->first();
            session(['active_finance_family_id' => $firstFamily->id]);
            return $firstFamily;
        }

        return null;
    }

    /**
     * Check if user can access the given family.
     */
    protected function canAccessFamily(Family $family): bool
    {
        $user = Auth::user();
        
        $hasRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
        
        $isMember = \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
        
        return $hasRole || $isMember;
    }
}

