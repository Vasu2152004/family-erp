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

            // Find existing role or create new one
            $userRole = FamilyUserRole::where('family_id', $familyId)
                ->where('user_id', $userId)
                ->first();

            if ($userRole) {
                // Force update existing role using direct DB query to ensure it works
                DB::table('family_user_roles')
                    ->where('id', $userRole->id)
                    ->update([
                        'role' => $role,
                        'is_backup_admin' => $isBackupAdmin,
                        'tenant_id' => $family->tenant_id,
                        'updated_at' => now(),
                    ]);
            } else {
                // Create new role
                $userRole = FamilyUserRole::create([
                    'tenant_id' => $family->tenant_id,
                    'family_id' => $familyId,
                    'user_id' => $userId,
                    'role' => $role,
                    'is_backup_admin' => $isBackupAdmin,
                ]);
            }

            // Clear all caches
            $this->clearRoleCache($userId, $familyId);
            
            // Return fresh instance from database (not from model cache)
            return FamilyUserRole::where('family_id', $familyId)
                ->where('user_id', $userId)
                ->firstOrFail();
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

            // Get requesting user
            $requestingUser = \App\Models\User::findOrFail($userId);

            // Get all admins and owners for this family (excluding deceased)
            $adminsAndOwners = FamilyUserRole::where('family_id', $familyId)
                ->whereIn('role', ['OWNER', 'ADMIN'])
                ->whereHas('user', function ($query) {
                    $query->where(function ($q) {
                        $q->whereHas('familyMember', function ($memberQuery) {
                            $memberQuery->where('is_deceased', false);
                        })->orWhereDoesntHave('familyMember');
                    });
                })
                ->with('user')
                ->get();

            // Create notifications for all admins and owners
            foreach ($adminsAndOwners as $role) {
                \App\Models\Notification::create([
                    'tenant_id' => $family->tenant_id,
                    'user_id' => $role->user_id,
                    'type' => 'admin_role_request',
                    'title' => 'Admin Role Request',
                    'message' => "{$requestingUser->name} has requested admin role for {$family->name}. This is request #{$request->request_count} of 3.",
                    'data' => [
                        'family_id' => $familyId,
                        'family_name' => $family->name,
                        'request_id' => $request->id,
                        'requesting_user_id' => $userId,
                        'requesting_user_name' => $requestingUser->name,
                        'request_count' => $request->request_count,
                    ],
                ]);
            }

            // If this is the 3rd request, check for auto-promotion immediately
            if ($request->request_count >= 3) {
                $promotedRole = $this->checkAndAutoPromote($familyId, $request->id);
                if ($promotedRole) {
                    $request->refresh();
                }
            }

            return $request->fresh();
        });
    }

    public function checkAndAutoPromote(int $familyId, ?int $specificRequestId = null): ?FamilyUserRole
    {
        return DB::transaction(function () use ($familyId, $specificRequestId) {
            // Find the eligible request (3+ requests, pending status)
            $query = AdminRoleRequest::where('family_id', $familyId)
                ->where('status', 'pending')
                ->where('request_count', '>=', 3);
            
            // If specific request ID provided, check that one first
            if ($specificRequestId) {
                $query->where('id', $specificRequestId);
            }
            
            $request = $query->orderBy('last_requested_at', 'asc')->first();

            if (!$request) {
                return null;
            }

            // Check for active OWNER or ADMIN roles (excluding deceased members)
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

            // Check if any admin/owner has responded to this request's notifications
            // Response means they read the notification (indicating awareness)
            // Get all notifications for this request and check if any are read
            $notifications = \App\Models\Notification::where('type', 'admin_role_request')
                ->get()
                ->filter(function($notification) use ($request) {
                    $data = $notification->data ?? [];
                    return isset($data['request_id']) && $data['request_id'] == $request->id;
                });
            
            $hasResponse = $notifications->contains(function($notification) {
                return $notification->read_at !== null;
            });

            // Auto-promote conditions (regardless of active admins):
            // 1. If NO active admins exist → immediate promotion
            // 2. If active admins exist BUT no response → also auto-promote
            //    - Immediately on 3rd request if no response yet
            //    - OR after 2 days from 3rd request if still no response
            $shouldAutoPromote = false;
            
            if (!$activeAdmins) {
                // No active admins - immediate auto-promotion
                $shouldAutoPromote = true;
            } elseif ($request->request_count >= 3 && !$hasResponse) {
                // Active admins exist but haven't responded
                // Auto-promote immediately on 3rd request (no waiting period)
                $shouldAutoPromote = true;
            }

            if ($shouldAutoPromote) {
                // Force assign ADMIN role (will update existing MEMBER/VIEWER role or create new)
                $role = $this->assignRole($request->user_id, $familyId, 'ADMIN', false);
                
                // Double-check: Query database directly to verify role was set
                $verifiedRole = FamilyUserRole::where('family_id', $familyId)
                    ->where('user_id', $request->user_id)
                    ->first();
                
                // If role is still not ADMIN, force update it directly using DB query
                if (!$verifiedRole || $verifiedRole->role !== 'ADMIN') {
                    if ($verifiedRole) {
                        // Update existing using raw DB query to bypass any model issues
                        DB::table('family_user_roles')
                            ->where('id', $verifiedRole->id)
                            ->update(['role' => 'ADMIN', 'updated_at' => now()]);
                    } else {
                        // Create new
                        $family = \App\Models\Family::findOrFail($familyId);
                        DB::table('family_user_roles')->insert([
                            'tenant_id' => $family->tenant_id,
                            'family_id' => $familyId,
                            'user_id' => $request->user_id,
                            'role' => 'ADMIN',
                            'is_backup_admin' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    // Get fresh instance
                    $role = FamilyUserRole::where('family_id', $familyId)
                        ->where('user_id', $request->user_id)
                        ->firstOrFail();
                }
                
                $request->update(['status' => 'auto_promoted']);
                $this->clearRoleCache($request->user_id, $familyId);
                
                return $role;
            }

            // Admins have responded, don't auto-promote
            return null;
        });
    }

    /**
     * Approve an admin role request.
     */
    public function approveAdminRoleRequest(int $requestId, int $approvedByUserId): FamilyUserRole
    {
        return DB::transaction(function () use ($requestId, $approvedByUserId) {
            $request = AdminRoleRequest::findOrFail($requestId);
            
            // Verify the approver is an admin or owner of the family
            $approverRole = FamilyUserRole::where('family_id', $request->family_id)
                ->where('user_id', $approvedByUserId)
                ->first();
            
            if (!$approverRole || ($approverRole->role !== 'OWNER' && $approverRole->role !== 'ADMIN')) {
                throw ValidationException::withMessages([
                    'request' => ['Only admins and owners can approve admin role requests.'],
                ]);
            }
            
            // Check if request is still pending
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => ['This request has already been processed.'],
                ]);
            }
            
            // Assign ADMIN role to the requesting user
            $role = $this->assignRole($request->user_id, $request->family_id, 'ADMIN', false);
            
            // Update request status to approved
            $request->update(['status' => 'approved']);
            
            // Clear cache
            $this->clearRoleCache($request->user_id, $request->family_id);
            
            // Create notification for the requesting user
            $family = \App\Models\Family::findOrFail($request->family_id);
            $approver = \App\Models\User::findOrFail($approvedByUserId);
            \App\Models\Notification::create([
                'tenant_id' => $family->tenant_id,
                'user_id' => $request->user_id,
                'type' => 'admin_role_approved',
                'title' => 'Admin Role Approved',
                'message' => "Your admin role request for {$family->name} has been approved by {$approver->name}.",
                'data' => [
                    'family_id' => $request->family_id,
                    'family_name' => $family->name,
                    'request_id' => $request->id,
                    'approved_by_user_id' => $approvedByUserId,
                    'approved_by_user_name' => $approver->name,
                ],
            ]);
            
            return $role;
        });
    }

    /**
     * Reject an admin role request.
     */
    public function rejectAdminRoleRequest(int $requestId, int $rejectedByUserId): void
    {
        DB::transaction(function () use ($requestId, $rejectedByUserId) {
            $request = AdminRoleRequest::findOrFail($requestId);
            
            // Verify the rejector is an admin or owner of the family
            $rejectorRole = FamilyUserRole::where('family_id', $request->family_id)
                ->where('user_id', $rejectedByUserId)
                ->first();
            
            if (!$rejectorRole || ($rejectorRole->role !== 'OWNER' && $rejectorRole->role !== 'ADMIN')) {
                throw ValidationException::withMessages([
                    'request' => ['Only admins and owners can reject admin role requests.'],
                ]);
            }
            
            // Check if request is still pending
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => ['This request has already been processed.'],
                ]);
            }
            
            // Update request status to rejected
            $request->update(['status' => 'rejected']);
            
            // Create notification for the requesting user
            $family = \App\Models\Family::findOrFail($request->family_id);
            $rejector = \App\Models\User::findOrFail($rejectedByUserId);
            \App\Models\Notification::create([
                'tenant_id' => $family->tenant_id,
                'user_id' => $request->user_id,
                'type' => 'admin_role_rejected',
                'title' => 'Admin Role Rejected',
                'message' => "Your admin role request for {$family->name} has been rejected by {$rejector->name}.",
                'data' => [
                    'family_id' => $request->family_id,
                    'family_name' => $family->name,
                    'request_id' => $request->id,
                    'rejected_by_user_id' => $rejectedByUserId,
                    'rejected_by_user_name' => $rejector->name,
                ],
            ]);
        });
    }

    private function clearRoleCache(int $userId, int $familyId): void
    {
        Cache::forget("user_role_{$userId}_{$familyId}");
        Cache::forget("family_roles_{$familyId}");
    }
}
