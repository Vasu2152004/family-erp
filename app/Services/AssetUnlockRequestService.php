<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetUnlockAccess;
use App\Models\AssetUnlockRequest;
use App\Models\FamilyUserRole;
use App\Models\Notification as AppNotification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetUnlockRequestService
{
    /**
     * Create or bump an unlock request for a locked asset whose owner is deceased.
     */
    public function createUnlockRequest(int $assetId, int $userId): AssetUnlockRequest
    {
        return DB::transaction(function () use ($assetId, $userId) {
            $asset = Asset::with(['familyMember', 'createdBy.familyMember'])->findOrFail($assetId);
            $family = $asset->family;

            if (!$asset->is_locked) {
                throw ValidationException::withMessages([
                    'asset' => ['This asset is not locked and does not require unlock.'],
                ]);
            }

            if (!$asset->isOwnerDeceased()) {
                throw ValidationException::withMessages([
                    'asset' => ['Asset owner must be deceased to request unlock.'],
                ]);
            }

            $user = User::findOrFail($userId);
            $role = $user->getFamilyRole($asset->family_id);
            if (!$role || !in_array($role->role, ['OWNER', 'ADMIN'])) {
                throw ValidationException::withMessages([
                    'user' => ['Only ADMIN or OWNER can request asset unlock.'],
                ]);
            }

            $request = AssetUnlockRequest::where('asset_id', $assetId)
                ->where('status', 'pending')
                ->orderByDesc('last_requested_at')
                ->first();

            $lastRequestAt = $request?->last_requested_at
                ? Carbon::parse($request->last_requested_at)
                : null;

            if ($lastRequestAt) {
                $nextRequestDate = $lastRequestAt->copy()->addDays(2);
                if (now()->lt($nextRequestDate)) {
                    $daysRemaining = now()->diffInDays($nextRequestDate, false) + 1;
                    throw ValidationException::withMessages([
                        'request' => ["You can only request unlock again after 2 days. Try again in {$daysRemaining} day(s)."],
                    ]);
                }
            }

            if (!$request) {
                $request = AssetUnlockRequest::create([
                    'tenant_id' => $family->tenant_id,
                    'family_id' => $asset->family_id,
                    'asset_id' => $assetId,
                    'requested_by' => $userId,
                    'request_count' => 1,
                    'last_requested_at' => now(),
                    'status' => 'pending',
                ]);
            } else {
                $request->request_count++;
                $request->last_requested_at = now();
                $request->requested_by = $userId;
                $request->save();
            }

            // Notify other admins/owners
            $adminsAndOwners = FamilyUserRole::where('family_id', $asset->family_id)
                ->whereIn('role', ['OWNER', 'ADMIN'])
                ->where('user_id', '!=', $userId)
                ->whereHas('user', function ($query) {
                    $query->where(function ($q) {
                        $q->whereHas('familyMember', function ($memberQuery) {
                            $memberQuery->where('is_deceased', false);
                        })->orWhereDoesntHave('familyMember');
                    });
                })
                ->with('user')
                ->get();

            foreach ($adminsAndOwners as $role) {
                AppNotification::create([
                    'tenant_id' => $family->tenant_id,
                    'user_id' => $role->user_id,
                    'type' => 'asset_unlock_request',
                    'title' => 'Asset Unlock Request',
                    'message' => "{$user->name} requested to unlock asset '{$asset->name}' for {$family->name}. This is request #{$request->request_count} of 3.",
                    'data' => [
                        'family_id' => $asset->family_id,
                        'family_name' => $family->name,
                        'asset_id' => $assetId,
                        'asset_name' => $asset->name,
                        'request_id' => $request->id,
                        'requesting_user_id' => $userId,
                        'requesting_user_name' => $user->name,
                        'request_count' => $request->request_count,
                    ],
                ]);
            }

            // Auto-unlock on 3rd request
            if ($request->request_count >= 3) {
                $this->checkAndAutoUnlock($assetId, $request->id);
                $request->refresh();
            }

            return $request->fresh();
        });
    }

    public function checkAndAutoUnlock(int $assetId, ?int $specificRequestId = null): ?AssetUnlockAccess
    {
        return DB::transaction(function () use ($assetId, $specificRequestId) {
            $asset = Asset::findOrFail($assetId);

            if (!$asset->is_locked) {
                return null;
            }

            $query = AssetUnlockRequest::where('asset_id', $assetId)
                ->where('status', 'pending')
                ->where('request_count', '>=', 3);

            if ($specificRequestId) {
                $query->where('id', $specificRequestId);
            }

            $request = $query->orderBy('last_requested_at', 'asc')->first();
            if (!$request) {
                return null;
            }

            // Simplified: after 3 requests, unlock for everyone
            $asset->is_locked = false;
            $asset->pin_hash = null;
            if (!empty($asset->encrypted_notes)) {
                $asset->decryptNotes();
                $asset->encrypted_notes = null;
            }
            $asset->save();

            // Mark this request (and any other pending ones) as auto_unlocked
            AssetUnlockRequest::where('asset_id', $assetId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'auto_unlocked',
                    'last_requested_at' => now(),
                ]);

            // No per-user access record needed; unlocked globally
            return null;
        });
    }
}
