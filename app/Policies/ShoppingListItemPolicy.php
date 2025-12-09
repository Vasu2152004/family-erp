<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Family;
use App\Models\ShoppingListItem;
use App\Models\User;

class ShoppingListItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Family $family): bool
    {
        // All family members can view shopping list
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
    public function view(User $user, ShoppingListItem $shoppingListItem): bool
    {
        // All family members can view shopping list items
        $userRole = \App\Models\FamilyUserRole::where('family_id', $shoppingListItem->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $shoppingListItem->family_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Family $family): bool
    {
        // All family members can create shopping list items
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
    public function update(User $user, ShoppingListItem $shoppingListItem): bool
    {
        // All family members can update shopping list items
        $userRole = \App\Models\FamilyUserRole::where('family_id', $shoppingListItem->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $shoppingListItem->family_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShoppingListItem $shoppingListItem): bool
    {
        // All family members can delete their own items or purchased items
        $userRole = \App\Models\FamilyUserRole::where('family_id', $shoppingListItem->family_id)
            ->where('user_id', $user->id)
            ->first();

        // Can delete if it's their own item
        if ($shoppingListItem->added_by === $user->id) {
            return true;
        }

        // Can delete if it's purchased
        if ($shoppingListItem->is_purchased) {
            return true;
        }

        // All family members can delete
        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $shoppingListItem->family_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can mark item as purchased.
     */
    public function markPurchased(User $user, ShoppingListItem $shoppingListItem): bool
    {
        // All family members can mark items as purchased
        $userRole = \App\Models\FamilyUserRole::where('family_id', $shoppingListItem->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole !== null) {
            return true;
        }

        return \App\Models\FamilyMember::where('family_id', $shoppingListItem->family_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
