<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Family;
use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Family $family): bool
    {
        // All family members can view items
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
    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        // All family members can view items
        $userRole = \App\Models\FamilyUserRole::where('family_id', $inventoryItem->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $inventoryItem->family_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Family $family): bool
    {
        // All family members can create items
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
    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        // All family members can update items (including custom fields like notes, location, etc.)
        $userRole = \App\Models\FamilyUserRole::where('family_id', $inventoryItem->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // Creator can update
        if ($inventoryItem->created_by === $user->id) {
            return true;
        }

        // All family members can update (including all fields and custom fields)
        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $inventoryItem->family_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        // Only OWNER/ADMIN can delete
        $userRole = \App\Models\FamilyUserRole::where('family_id', $inventoryItem->family_id)
            ->where('user_id', $user->id)
            ->first();

        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }
}
