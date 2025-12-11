<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DoctorVisit;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;

class DoctorVisitPolicy
{
    public function viewAny(User $user, Family $family): bool
    {
        return $this->userHasFamilyAccess($user, $family->id);
    }

    public function view(User $user, DoctorVisit $visit): bool
    {
        return $this->userHasFamilyAccess($user, $visit->family_id);
    }

    public function create(User $user, Family $family): bool
    {
        if ($this->isOwnerOrAdmin($user, $family->id)) {
            return true;
        }

        return FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, DoctorVisit $visit): bool
    {
        if ($this->isOwnerOrAdmin($user, $visit->family_id)) {
            return true;
        }

        return $this->isLinkedMember($user, $visit->family_member_id);
    }

    public function delete(User $user, DoctorVisit $visit): bool
    {
        return $this->update($user, $visit);
    }

    private function userHasFamilyAccess(User $user, int $familyId): bool
    {
        return FamilyUserRole::where('family_id', $familyId)
            ->where('user_id', $user->id)
            ->exists()
            || FamilyMember::where('family_id', $familyId)
                ->where('user_id', $user->id)
                ->exists();
    }

    private function isOwnerOrAdmin(User $user, int $familyId): bool
    {
        $role = FamilyUserRole::where('family_id', $familyId)
            ->where('user_id', $user->id)
            ->first();

        return $role !== null && in_array($role->role, ['OWNER', 'ADMIN'], true);
    }

    private function isLinkedMember(User $user, int $memberId): bool
    {
        $member = FamilyMember::find($memberId);
        return $member !== null && $member->user_id === $user->id;
    }
}

