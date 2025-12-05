<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Family;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FamilyRolePolicy
{
    /**
     * Determine whether the user can view any families.
     */
    public function viewAny(User $user): bool
    {
        // User can view any families they are associated with (via role or member record)
        return \App\Models\FamilyUserRole::where('user_id', $user->id)->exists()
            || \App\Models\FamilyMember::where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view the family.
     */
    public function view(User $user, Family $family): bool
    {
        // User can view a family if they have a role in it OR are a member of it
        return $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create families.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user with tenant_id can create families
    }

    /**
     * Determine whether the user can update the family.
     */
    public function update(User $user, Family $family): bool
    {
        // Query database directly (bypass cache) to ensure fresh data
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();
        
        // Allow if user has OWNER or ADMIN role (regardless of tenant_id - families can have cross-tenant members)
        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }

    /**
     * Determine whether the user can delete the family.
     */
    public function delete(User $user, Family $family): bool
    {
        // Only owners can delete, and they must be from the same tenant as the family
        // (deletion is more restrictive than other operations)
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();
        
        return $userRole && $userRole->role === 'OWNER' && $user->tenant_id === $family->tenant_id;
    }

    /**
     * Determine whether the user can manage the family (OWNER/ADMIN only).
     */
    public function manageFamily(User $user, Family $family): bool
    {
        // Query database directly (bypass cache) to ensure fresh data
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();
        
        // Allow if user has OWNER or ADMIN role (regardless of tenant_id - families can have cross-tenant members)
        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }
}
