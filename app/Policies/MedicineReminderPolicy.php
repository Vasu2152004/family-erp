<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MedicineReminder;
use App\Models\User;
use App\Models\FamilyMember;

class MedicineReminderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MedicineReminder $medicineReminder): bool
    {
        if ($user->tenant_id !== $medicineReminder->tenant_id) {
            return false;
        }

        return $this->belongsToFamily($user, $medicineReminder);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MedicineReminder $medicineReminder): bool
    {
        if ($user->tenant_id !== $medicineReminder->tenant_id) {
            return false;
        }

        return $user->isFamilyAdmin($medicineReminder->family_id) || $this->isLinkedMember($user, $medicineReminder);
    }

    public function delete(User $user, MedicineReminder $medicineReminder): bool
    {
        return $this->update($user, $medicineReminder);
    }

    private function belongsToFamily(User $user, MedicineReminder $medicineReminder): bool
    {
        $role = $user->getFamilyRole($medicineReminder->family_id);
        $isMember = FamilyMember::where('family_id', $medicineReminder->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }

    private function isLinkedMember(User $user, MedicineReminder $medicineReminder): bool
    {
        if (!$medicineReminder->family_member_id) {
            return false;
        }

        return FamilyMember::where('id', $medicineReminder->family_member_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
