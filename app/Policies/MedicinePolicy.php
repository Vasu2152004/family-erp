<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Medicine;
use App\Models\User;
use App\Models\FamilyMember;

class MedicinePolicy
{
    /**
     * Determine if the user can view any medicines.
     * All family members can view.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the medicine.
     * All family members can view medicines in their family.
     */
    public function view(User $user, Medicine $medicine): bool
    {
        if ($user->tenant_id !== $medicine->tenant_id) {
            return false;
        }

        return $this->belongsToFamily($user, $medicine);
    }

    /**
     * Determine if the user can create medicines.
     * All family members can create.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the medicine.
     * Family admin, medicine owner (family_member), creator, or any family member can update.
     */
    public function update(User $user, Medicine $medicine): bool
    {
        if ($user->tenant_id !== $medicine->tenant_id) {
            return false;
        }

        if (!$this->belongsToFamily($user, $medicine)) {
            return false;
        }

        // Family admin or medicine owner (family_member) or creator can update
        if ($user->isFamilyAdmin($medicine->family_id)) {
            return true;
        }
        if ($medicine->familyMember && $medicine->familyMember->user_id === $user->id) {
            return true;
        }
        if ($medicine->created_by === $user->id) {
            return true;
        }

        return true; // All family members can update (shared medicine cabinet)
    }

    /**
     * Determine if the user can delete the medicine.
     */
    public function delete(User $user, Medicine $medicine): bool
    {
        return $this->update($user, $medicine);
    }

    /**
     * Check if user belongs to the same family as the medicine.
     */
    private function belongsToFamily(User $user, Medicine $medicine): bool
    {
        $role = $user->getFamilyRole($medicine->family_id);
        $isMember = FamilyMember::where('family_id', $medicine->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }
}














