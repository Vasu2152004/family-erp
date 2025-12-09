<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any transactions for a family.
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

        // Any role (including MEMBER) can view
        if ($userRole !== null) {
            return true;
        }

        // Also check if user is a family member
        return \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        // OWNER/ADMIN can view all
        $userRole = \App\Models\FamilyUserRole::where('family_id', $transaction->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // Check if user is a family member (with or without role)
        $isFamilyMember = \App\Models\FamilyMember::where('family_id', $transaction->family_id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isFamilyMember && !$userRole) {
            return false;
        }

        // MEMBER or family member can view if it's their transaction or if it's shared
        return $transaction->canView($user);
    }

    /**
     * Determine whether the user can create transactions for a family.
     */
    public function create(User $user, Family $family): bool
    {
        // OWNER/ADMIN can create all (prioritize admin access)
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // Any role (including MEMBER) can create
        if ($userRole !== null) {
            return true;
        }

        // Also check if user is a family member (can create transactions)
        return \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the transaction.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // OWNER/ADMIN can update all
        $userRole = \App\Models\FamilyUserRole::where('family_id', $transaction->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // MEMBER or family member can update if it's their transaction
        if ($transaction->family_member_id) {
            $member = \App\Models\FamilyMember::find($transaction->family_member_id);
            if ($member && $member->user_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the transaction.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // OWNER/ADMIN can delete all
        $userRole = \App\Models\FamilyUserRole::where('family_id', $transaction->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // MEMBER or family member can delete if it's their transaction
        if ($transaction->family_member_id) {
            $member = \App\Models\FamilyMember::find($transaction->family_member_id);
            if ($member && $member->user_id === $user->id) {
                return true;
            }
        }

        return false;
    }
}
