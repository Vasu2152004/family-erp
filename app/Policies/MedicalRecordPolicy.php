<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\FamilyMember;

class MedicalRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MedicalRecord $medicalRecord): bool
    {
        if ($user->tenant_id !== $medicalRecord->tenant_id) {
            return false;
        }

        return $this->belongsToFamily($user, $medicalRecord);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MedicalRecord $medicalRecord): bool
    {
        if ($user->tenant_id !== $medicalRecord->tenant_id) {
            return false;
        }

        return $user->isFamilyAdmin($medicalRecord->family_id) || $this->isLinkedMember($user, $medicalRecord);
    }

    public function delete(User $user, MedicalRecord $medicalRecord): bool
    {
        return $this->update($user, $medicalRecord);
    }

    private function belongsToFamily(User $user, MedicalRecord $medicalRecord): bool
    {
        $role = $user->getFamilyRole($medicalRecord->family_id);
        $isMember = FamilyMember::where('family_id', $medicalRecord->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }

    private function isLinkedMember(User $user, MedicalRecord $medicalRecord): bool
    {
        if (!$medicalRecord->family_member_id) {
            return false;
        }

        return FamilyMember::where('id', $medicalRecord->family_member_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
