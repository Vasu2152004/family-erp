<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Investment;
use App\Models\Family;
use App\Models\FamilyMember;

class InvestmentPolicy
{
    /**
     * Determine whether the user can view any investments.
     */
    public function viewAny(User $user, Family $family): bool
    {
        $role = $user->getFamilyRole($family->id);
        $isLinkedMember = FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
        
        if (!$role && !$isLinkedMember) {
            return false;
        }

        // OWNER/ADMIN can view all
        if (in_array($role->role ?? null, ['OWNER', 'ADMIN'])) {
            return true;
        }

        // MEMBER or linked family member can view list
        return ($role && $role->role === 'MEMBER') || $isLinkedMember;
    }

    /**
     * Determine whether the user can view the investment.
     */
    public function view(User $user, Investment $investment): bool
    {
        if ($investment->isEffectiveOwner($user)) {
            return true;
        }

        $role = $user->getFamilyRole($investment->family_id);
        $isLinkedMember = FamilyMember::where('family_id', $investment->family_id)
            ->where('user_id', $user->id)
            ->exists();
        
        if (!$role && !$isLinkedMember) {
            return false;
        }

        // If investment is not hidden, check normal permissions
        if (!$investment->is_hidden) {
            // OWNER/ADMIN/MEMBER can view all visible investments; linked members too
            if ($role && in_array($role->role, ['OWNER', 'ADMIN', 'MEMBER'])) {
                return true;
            }

            return $isLinkedMember;
        }

        // Hidden investment: check if user has unlock access
        if ($investment->isUnlockedFor($user)) {
            return true;
        }

        // Check if user is the owner (linked) or creator fallback
        if ($investment->isEffectiveOwner($user)) {
            return true;
        }

        // OWNER/ADMIN can see hidden investments (but need PIN or request to unlock)
        return in_array($role->role ?? null, ['OWNER', 'ADMIN']);
    }

    /**
     * Determine whether the user can create investments.
     */
    public function create(User $user, Family $family): bool
    {
        $role = $user->getFamilyRole($family->id);
        
        if (!$role) {
            return false;
        }

        // OWNER/ADMIN/MEMBER can create
        return in_array($role->role, ['OWNER', 'ADMIN', 'MEMBER']);
    }

    /**
     * Determine whether the user can update the investment.
     */
    public function update(User $user, Investment $investment): bool
    {
        if ($investment->isEffectiveOwner($user)) {
            return true;
        }

        $role = $user->getFamilyRole($investment->family_id);
        
        if (!$role) {
            return false;
        }

        // OWNER/ADMIN can update all
        if (in_array($role->role, ['OWNER', 'ADMIN'])) {
            return true;
        }

        // MEMBER can update own investments
        if ($role->role === 'MEMBER' && $investment->familyMember && $investment->familyMember->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the investment.
     */
    public function delete(User $user, Investment $investment): bool
    {
        return $this->update($user, $investment);
    }

    /**
     * Determine whether the user can request unlock for the investment.
     */
    public function requestUnlock(User $user, Investment $investment): bool
    {
        $role = $user->getFamilyRole($investment->family_id);
        
        if (!$role) {
            return false;
        }

        // Only OWNER/ADMIN can request unlock
        if (!in_array($role->role, ['OWNER', 'ADMIN'])) {
            return false;
        }

        // Investment must be hidden and owner must be deceased
        return $investment->canBeRequestedForUnlock($user);
    }

    /**
     * Determine whether the user can unlock the investment.
     */
    public function unlock(User $user, Investment $investment): bool
    {
        if ($investment->isEffectiveOwner($user)) {
            return true;
        }

        $role = $user->getFamilyRole($investment->family_id);
        
        if (!$role) {
            return false;
        }

        // If already unlocked for user, can access
        if ($investment->isUnlockedFor($user)) {
            return true;
        }

        // If user is owner, can unlock with PIN
        if ($investment->familyMember && $investment->familyMember->user_id === $user->id) {
            return true;
        }

        // OWNER/ADMIN can unlock with PIN
        return in_array($role->role, ['OWNER', 'ADMIN']);
    }
}




