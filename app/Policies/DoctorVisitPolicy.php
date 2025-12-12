<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DoctorVisit;
use App\Models\User;
use App\Models\FamilyMember;

class DoctorVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DoctorVisit $doctorVisit): bool
    {
        if ($user->tenant_id !== $doctorVisit->tenant_id) {
            return false;
        }

        return $this->belongsToFamily($user, $doctorVisit);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DoctorVisit $doctorVisit): bool
    {
        if ($user->tenant_id !== $doctorVisit->tenant_id) {
            return false;
        }

        return $user->isFamilyAdmin($doctorVisit->family_id) || $this->isLinkedMember($user, $doctorVisit);
    }

    public function delete(User $user, DoctorVisit $doctorVisit): bool
    {
        return $this->update($user, $doctorVisit);
    }

    private function belongsToFamily(User $user, DoctorVisit $doctorVisit): bool
    {
        $role = $user->getFamilyRole($doctorVisit->family_id);
        $isMember = FamilyMember::where('family_id', $doctorVisit->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }

    private function isLinkedMember(User $user, DoctorVisit $doctorVisit): bool
    {
        if (!$doctorVisit->family_member_id) {
            return false;
        }

        return FamilyMember::where('id', $doctorVisit->family_member_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
