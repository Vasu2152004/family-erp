<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Vehicle;
use App\Models\User;
use App\Models\FamilyMember;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        if (!$this->belongsToFamily($user, $vehicle)) {
            return false;
        }

        // All family members can view all vehicles
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        if (!$this->belongsToFamily($user, $vehicle)) {
            return false;
        }

        // VIEWER cannot update
        $role = $user->getFamilyRole($vehicle->family_id);
        if ($role && $role->role === 'viewer') {
            return false;
        }

        // OWNER/ADMIN can update all
        if ($user->isFamilyAdmin($vehicle->family_id)) {
            return true;
        }

        // MEMBER can update if they own the vehicle or created it
        if ($role && $role->role === 'member') {
            return $this->isVehicleOwner($user, $vehicle) || $vehicle->created_by === $user->id;
        }

        return false;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        if (!$this->belongsToFamily($user, $vehicle)) {
            return false;
        }

        // Only OWNER/ADMIN can delete
        return $user->isFamilyAdmin($vehicle->family_id);
    }

    private function belongsToFamily(User $user, Vehicle $vehicle): bool
    {
        $role = $user->getFamilyRole($vehicle->family_id);
        $isMember = FamilyMember::where('family_id', $vehicle->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }

    private function isVehicleOwner(User $user, Vehicle $vehicle): bool
    {
        if (!$vehicle->family_member_id) {
            return false;
        }

        return FamilyMember::where('id', $vehicle->family_member_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}















