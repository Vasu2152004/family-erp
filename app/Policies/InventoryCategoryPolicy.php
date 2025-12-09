<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Family;
use App\Models\InventoryCategory;
use App\Models\User;

class InventoryCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Family $family): bool
    {
        // All family members can view categories
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryCategory $inventoryCategory): bool
    {
        // All family members can view categories
        $userRole = \App\Models\FamilyUserRole::where('family_id', $inventoryCategory->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $inventoryCategory->family_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Family $family): bool
    {
        // All family members can create categories
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventoryCategory $inventoryCategory): bool
    {
        // OWNER/ADMIN or creator can update
        $userRole = \App\Models\FamilyUserRole::where('family_id', $inventoryCategory->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // Creator can update
        if ($inventoryCategory->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryCategory $inventoryCategory): bool
    {
        // Only OWNER/ADMIN can delete
        $userRole = \App\Models\FamilyUserRole::where('family_id', $inventoryCategory->family_id)
            ->where('user_id', $user->id)
            ->first();

        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }
}
