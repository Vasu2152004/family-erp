<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminRoleRequest;
use App\Models\FamilyUserRole;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

            $request = AdminRoleRequest::where('family_id', $familyId)
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            // If request exists, check if 2 days have passed since last request
            if ($request && $request->last_requested_at) {
                $nextRequestDate = $request->last_requested_at->copy()->addDays(2);
                
                if ($nextRequestDate->isFuture()) {
                    $daysRemaining = now()->diffInDays($nextRequestDate, false) + 1;
                    throw ValidationException::withMessages([
                        'request' => ["You can only request admin role again after 2 days from your last request. Please try again in {$daysRemaining} day(s)."],
                    ]);
                }
            }

            // Create new request or update existing one
            if (!$request) {
                $request = AdminRoleRequest::create([
                    'tenant_id' => $family->tenant_id,
                    'family_id' => $familyId,
                    'user_id' => $userId,
                    'request_count' => 1,
                    'last_requested_at' => now(),
                    'status' => 'pending',
                ]);
            } else {
                $request->request_count++;
                $request->last_requested_at = now();
                $request->save();
            }

            // If this is the 3rd request, check for auto-promotion
            if ($request->request_count >= 3) {
                $promotedRole = $this->checkAndAutoPromote($familyId);
                if ($promotedRole) {
                    $request->refresh();
                }
            }

            return $request->fresh();
        });
    }

    public function checkAndAutoPromote(int $familyId): ?FamilyUserRole
    {
        return DB::transaction(function () use ($familyId) {
            // Check for active OWNER or ADMIN roles (excluding deceased members)
            // A user is active if they don't have a familyMember OR their familyMember is not deceased
            $activeAdmins = FamilyUserRole::where('family_id', $familyId)
                ->whereIn('role', ['OWNER', 'ADMIN'])
                ->whereHas('user', function ($query) {
                    $query->where(function ($q) {
                        $q->whereHas('familyMember', function ($memberQuery) {
                            $memberQuery->where('is_deceased', false);
                        })->orWhereDoesntHave('familyMember');
                    });
                })
                ->exists();

            // If active admins exist, don't auto-promote
            if ($activeAdmins) {
                return null;
            }

            // Find the first eligible request (3+ requests, pending status)
            $request = AdminRoleRequest::where('family_id', $familyId)
                ->where('status', 'pending')
                ->where('request_count', '>=', 3)
                ->orderBy('last_requested_at', 'asc')
                ->first();

            if (!$request) {
                return null;
            }

            // Auto-promote to ADMIN role
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
