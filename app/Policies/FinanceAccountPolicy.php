<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Family;
use App\Models\FinanceAccount;
use App\Models\User;

class FinanceAccountPolicy
{
    /**
     * Determine whether the user can view any finance accounts for a family.
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

        // Any role (including MEMBER) can view accounts
        if ($userRole !== null) {
            return true;
        }

        // Also check if user is a family member
        return \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the finance account.
     */
    public function view(User $user, FinanceAccount $financeAccount): bool
    {
        // OWNER/ADMIN can view all (prioritize admin access)
        $userRole = \App\Models\FamilyUserRole::where('family_id', $financeAccount->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // Any role (including MEMBER) can view accounts
        if ($userRole !== null) {
            return true;
        }

        // Also check if user is a family member
        return \App\Models\FamilyMember::where('family_id', $financeAccount->family_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create finance accounts for a family.
     */
    public function create(User $user, Family $family): bool
    {
        // OWNER/ADMIN can create accounts
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();

        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }

    /**
     * Determine whether the user can update the finance account.
     */
    public function update(User $user, FinanceAccount $financeAccount): bool
    {
        // OWNER/ADMIN can update accounts
        $userRole = \App\Models\FamilyUserRole::where('family_id', $financeAccount->family_id)
            ->where('user_id', $user->id)
            ->first();

        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }

    /**
     * Determine whether the user can delete the finance account.
     */
    public function delete(User $user, FinanceAccount $financeAccount): bool
    {
        // OWNER/ADMIN can delete accounts
        $userRole = \App\Models\FamilyUserRole::where('family_id', $financeAccount->family_id)
            ->where('user_id', $user->id)
            ->first();

        return $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
    }
}
