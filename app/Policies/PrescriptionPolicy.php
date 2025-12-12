<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;
use App\Models\FamilyMember;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Prescription $prescription): bool
    {
        if ($user->tenant_id !== $prescription->tenant_id) {
            return false;
        }

        return $this->belongsToFamily($user, $prescription);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Prescription $prescription): bool
    {
        if ($user->tenant_id !== $prescription->tenant_id) {
            return false;
        }

        return $user->isFamilyAdmin($prescription->family_id) || $this->isLinkedMember($user, $prescription);
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $this->update($user, $prescription);
    }

    private function belongsToFamily(User $user, Prescription $prescription): bool
    {
        $role = $user->getFamilyRole($prescription->family_id);
        $isMember = FamilyMember::where('family_id', $prescription->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }

    private function isLinkedMember(User $user, Prescription $prescription): bool
    {
        if (!$prescription->family_member_id) {
            return false;
        }

        return FamilyMember::where('id', $prescription->family_member_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
