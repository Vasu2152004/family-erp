<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MedicineReminder;
use App\Models\Prescription;
use App\Models\User;

class MedicineReminderPolicy
{
    public function view(User $user, MedicineReminder $reminder): bool
    {
        return $this->userHasFamilyAccess($user, $reminder->family_id);
    }

    public function create(User $user, Prescription $prescription): bool
    {
        if ($this->isOwnerOrAdmin($user, $prescription->family_id)) {
            return true;
        }

        return $this->isLinkedMember($user, $prescription->family_member_id);
    }

    public function update(User $user, MedicineReminder $reminder): bool
    {
        if ($this->isOwnerOrAdmin($user, $reminder->family_id)) {
            return true;
        }

        return $this->isLinkedMember($user, $reminder->family_member_id);
    }

    public function delete(User $user, MedicineReminder $reminder): bool
    {
        return $this->update($user, $reminder);
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

