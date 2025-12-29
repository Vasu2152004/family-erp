<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Asset;
use App\Models\Family;
use App\Models\AssetUnlockAccess;
use App\Models\FamilyMember;

class AssetPolicy
{
    /**
     * Determine whether the user can view any assets.
     */
    public function viewAny(User $user, Family $family): bool
    {
        $role = $user->getFamilyRole($family->id);
        $isLinkedMember = FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$role && !$isLinkedMember) {
            return false;
        }

        // OWNER/ADMIN or linked member can view list
        if ($role && in_array($role->role, ['OWNER', 'ADMIN', 'MEMBER'])) {
            return true;
        }

        return $isLinkedMember;
    }

    /**
     * Determine whether the user can view the asset.
     */
    public function view(User $user, Asset $asset): bool
    {
        if ($asset->isEffectiveOwner($user)) {
            return true;
        }

        // If user has explicit unlock access, allow
        $hasUnlockAccess = AssetUnlockAccess::where('asset_id', $asset->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($hasUnlockAccess) {
            return true;
        }

        $role = $user->getFamilyRole($asset->family_id);
        $isLinkedMember = FamilyMember::where('family_id', $asset->family_id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$role && !$isLinkedMember) {
            return false;
        }

        // OWNER/ADMIN can view all
        if ($role && in_array($role->role, ['OWNER', 'ADMIN'])) {
            return true;
        }

        // MEMBER or linked family member can view any asset within the same family
        if (($role && $role->role === 'MEMBER') || $isLinkedMember) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create assets.
     */
    public function create(User $user, Family $family): bool
    {
        $role = $user->getFamilyRole($family->id);

        // OWNER/ADMIN/MEMBER role can create
        if ($role && in_array($role->role, ['OWNER', 'ADMIN', 'MEMBER'])) {
            return true;
        }

        // Fallback: linked family member without explicit role can create
        return FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, Asset $asset): bool
    {
        if ($asset->isEffectiveOwner($user)) {
            return true;
        }

        $role = $user->getFamilyRole($asset->family_id);
        
        if (!$role) {
            return false;
        }

        // OWNER/ADMIN can update all
        if (in_array($role->role, ['OWNER', 'ADMIN'])) {
            return true;
        }

        // MEMBER can update own assets
        if ($role->role === 'MEMBER' && $asset->familyMember && $asset->familyMember->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, Asset $asset): bool
    {
        return $this->update($user, $asset);
    }
}









