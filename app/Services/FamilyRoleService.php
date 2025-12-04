<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminRoleRequest;
use App\Models\FamilyUserRole;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FamilyRoleService
{
    public function assignRole(int $userId, int $familyId, string $role, bool $isBackupAdmin = false): FamilyUserRole
    {
        return DB::transaction(function () use ($userId, $familyId, $role, $isBackupAdmin) {
            $family = \App\Models\Family::findOrFail($familyId);

            $userRole = FamilyUserRole::updateOrCreate(
                [
                    'family_id' => $familyId,
                    'user_id' => $userId,
                ],
                [
                    'tenant_id' => $family->tenant_id,
                    'role' => $role,
                    'is_backup_admin' => $isBackupAdmin,
                ]
            );

            $this->clearRoleCache($userId, $familyId);

            return $userRole;
        });
    }

    public function assignBackupAdmin(int $userId, int $familyId): FamilyUserRole
    {
        return DB::transaction(function () use ($userId, $familyId) {
            $role = FamilyUserRole::where('family_id', $familyId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $role->update(['is_backup_admin' => true]);

            $this->clearRoleCache($userId, $familyId);

            return $role->fresh();
        });
    }

    public function removeBackupAdmin(int $userId, int $familyId): FamilyUserRole
    {
        return DB::transaction(function () use ($userId, $familyId) {
            $role = FamilyUserRole::where('family_id', $familyId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $role->update(['is_backup_admin' => false]);

            $this->clearRoleCache($userId, $familyId);

            return $role->fresh();
        });
    }

    public function requestAdminRole(int $userId, int $familyId): AdminRoleRequest
    {
        return DB::transaction(function () use ($userId, $familyId) {
            $family = \App\Models\Family::findOrFail($familyId);

            $request = AdminRoleRequest::firstOrNew(
                [
                    'family_id' => $familyId,
                    'user_id' => $userId,
                    'status' => 'pending',
                ],
                [
                    'tenant_id' => $family->tenant_id,
                    'request_count' => 0,
                    'last_requested_at' => now(),
                ]
            );

            $request->request_count++;
            $request->last_requested_at = now();
            $request->save();

            if ($request->request_count >= 3) {
                $this->checkAndAutoPromote($familyId);
            }

            return $request->fresh();
        });
    }

    public function checkAndAutoPromote(int $familyId): ?FamilyUserRole
    {
        return DB::transaction(function () use ($familyId) {
            $activeAdmins = FamilyUserRole::where('family_id', $familyId)
                ->whereIn('role', ['OWNER', 'ADMIN'])
                ->whereHas('user.familyMember', function ($query) {
                    $query->where('is_deceased', false);
                })
                ->exists();

            if ($activeAdmins) {
                return null;
            }

            $request = AdminRoleRequest::where('family_id', $familyId)
                ->where('status', 'pending')
                ->where('request_count', '>=', 3)
                ->orderBy('last_requested_at', 'asc')
                ->first();

            if (!$request) {
                return null;
            }

            $role = $this->assignRole($request->user_id, $familyId, 'ADMIN');
            $request->update(['status' => 'auto_promoted']);

            $this->clearRoleCache($request->user_id, $familyId);

            return $role;
        });
    }

    private function clearRoleCache(int $userId, int $familyId): void
    {
        Cache::forget("user_role_{$userId}_{$familyId}");
        Cache::forget("family_roles_{$familyId}");
    }
}
