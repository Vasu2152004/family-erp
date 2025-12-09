<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Family;
use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    /**
     * Determine whether the user can view any budgets for a family.
     */
    public function viewAny(User $user, Family $family): bool
    {
        // OWNER/ADMIN can view all (prioritize admin access)
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // Any role (including MEMBER) can view budgets
        if ($userRole) {
            return true;
        }

        // Also check if user is a family member
        return \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the budget.
     */
    public function view(User $user, Budget $budget): bool
    {
        // OWNER/ADMIN can view all budgets
        $userRole = \App\Models\FamilyUserRole::where('family_id', $budget->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // MEMBER can view family budgets and their own personal budgets
        if ($userRole && $userRole->role === 'MEMBER') {
            // Can view family budgets (no family_member_id)
            if ($budget->family_member_id === null) {
                return true;
            }
            
            // Can view their own personal budgets
            $member = \App\Models\FamilyMember::find($budget->family_member_id);
            if ($member && $member->user_id === $user->id) {
                return true;
            }
        }

        // Also check if user is a family member (not through role)
        $isMember = \App\Models\FamilyMember::where('family_id', $budget->family_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($isMember) {
            // Can view family budgets
            if ($budget->family_member_id === null) {
                return true;
            }
            
            // Can view their own personal budgets
            $member = \App\Models\FamilyMember::find($budget->family_member_id);
            if ($member && $member->user_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create budgets for a family.
     */
    public function create(User $user, Family $family): bool
    {
        // OWNER/ADMIN can create budgets
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();

        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }

    /**
     * Determine whether the user can update the budget.
     */
    public function update(User $user, Budget $budget): bool
    {
        // OWNER/ADMIN can update budgets
        $userRole = \App\Models\FamilyUserRole::where('family_id', $budget->family_id)
            ->where('user_id', $user->id)
            ->first();

        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }

    /**
     * Determine whether the user can delete the budget.
     */
    public function delete(User $user, Budget $budget): bool
    {
        // OWNER/ADMIN can delete budgets
        $userRole = \App\Models\FamilyUserRole::where('family_id', $budget->family_id)
            ->where('user_id', $user->id)
            ->first();

        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }
}
