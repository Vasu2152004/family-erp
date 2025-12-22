<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Investment;
use App\Models\InvestmentUnlockRequest;
use App\Models\InvestmentUnlockAccess;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentUnlockRequestService
{
    /**
     * Create an unlock request for a hidden investment.
     */
    public function createUnlockRequest(int $investmentId, int $userId): InvestmentUnlockRequest
    {
        return DB::transaction(function () use ($investmentId, $userId) {
            $investment = Investment::with(['familyMember', 'createdBy.familyMember'])->findOrFail($investmentId);
            $family = $investment->family;

            // Validate: investment must be hidden
            if (!$investment->is_hidden) {
                throw ValidationException::withMessages([
                    'investment' => ['This investment is not hidden and does not require unlock.'],
                ]);
            }

            // Validate: owner must be deceased
            if (!$investment->isOwnerDeceased()) {
                throw ValidationException::withMessages([
                    'investment' => ['Investment owner must be deceased to request unlock.'],
                ]);
            }

            // Validate: user must be ADMIN or OWNER
            $user = User::findOrFail($userId);
            $role = $user->getFamilyRole($investment->family_id);
            if (!$role || !in_array($role->role, ['OWNER', 'ADMIN'])) {
                throw ValidationException::withMessages([
                    'user' => ['Only ADMIN or OWNER can request investment unlock.'],
                ]);
            }

            // Check for existing request
            $request = InvestmentUnlockRequest::where('investment_id', $investmentId)
                ->where('status', 'pending')
                ->orderByDesc('last_requested_at')
                ->first();

            $lastRequestAt = $request?->last_requested_at
                ? Carbon::parse($request->last_requested_at)
                : null;

            // If request exists, check if 2 days have passed since last request
            if ($lastRequestAt) {
                $nextRequestDate = $lastRequestAt->copy()->addDays(2);
                
                if (now()->lt($nextRequestDate)) {
                    $daysRemaining = now()->diffInDays($nextRequestDate, false) + 1;
                    throw ValidationException::withMessages([
                        'request' => ["You can only request unlock again after 2 days from the last request. Please try again in {$daysRemaining} day(s)."],
                    ]);
                }
            }

            // Create new request or update existing one
            if (!$request) {
                $request = InvestmentUnlockRequest::create([
                    'tenant_id' => $family->tenant_id,
                    'family_id' => $investment->family_id,
                    'investment_id' => $investmentId,
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

            // Get all admins and owners for this family (excluding deceased and requesting user)
            $adminsAndOwners = FamilyUserRole::where('family_id', $investment->family_id)
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

            // Create notifications for all admins and owners
            foreach ($adminsAndOwners as $role) {
                \App\Models\Notification::create([
                    'tenant_id' => $family->tenant_id,
                    'user_id' => $role->user_id,
                    'type' => 'investment_unlock_request',
                    'title' => 'Investment Unlock Request',
                    'message' => "{$user->name} has requested to unlock hidden investment '{$investment->name}' for {$family->name}. This is request #{$request->request_count} of 3.",
                    'data' => [
                        'family_id' => $investment->family_id,
                        'family_name' => $family->name,
                        'investment_id' => $investmentId,
                        'investment_name' => $investment->name,
                        'request_id' => $request->id,
                        'requesting_user_id' => $userId,
                        'requesting_user_name' => $user->name,
                        'request_count' => $request->request_count,
                    ],
                ]);
            }

            // If this is the 3rd request, check for auto-unlock immediately
            if ($request->request_count >= 3) {
                $this->checkAndAutoUnlock($investmentId, $request->id);
                $request->refresh();
            }

            return $request->fresh();
        });
    }

    /**
     * Check and auto-unlock investment if conditions are met.
     */
    public function checkAndAutoUnlock(int $investmentId, ?int $specificRequestId = null): ?InvestmentUnlockAccess
    {
        return DB::transaction(function () use ($investmentId, $specificRequestId) {
            $investment = Investment::findOrFail($investmentId);

            // Investment must still be hidden
            if (!$investment->is_hidden) {
                return null;
            }

            // Find the eligible request (3+ requests, pending status)
            $query = InvestmentUnlockRequest::where('investment_id', $investmentId)
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

            // Simplified: after 3 requests, make it visible to all
            $investment->is_hidden = false;
            $investment->pin_hash = null;
            if (!empty($investment->encrypted_details) && empty($investment->details)) {
                $investment->decryptDetails();
                $investment->encrypted_details = null;
            }
            $investment->save();

            // Mark this request (and any other pending ones) as auto_unlocked
            InvestmentUnlockRequest::where('investment_id', $investmentId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'auto_unlocked',
                    'last_requested_at' => now(),
                ]);

            // No per-user access record needed; unlocked globally
            return null;
        });
    }

    /**
     * Approve unlock request.
     */
    public function approveUnlockRequest(int $requestId, int $approvedBy): InvestmentUnlockAccess
    {
        return DB::transaction(function () use ($requestId, $approvedBy) {
            $request = InvestmentUnlockRequest::with('investment')->findOrFail($requestId);
            $investment = $request->investment;

            // Validate investment is still hidden
            if (!$investment->is_hidden) {
                throw ValidationException::withMessages([
                    'investment' => ['Investment is no longer hidden.'],
                ]);
            }

            // Create unlock access record
            $unlockAccess = InvestmentUnlockAccess::create([
                'investment_id' => $request->investment_id,
                'user_id' => $request->requested_by,
                'unlocked_at' => now(),
                'unlocked_via' => 'request_approved',
                'request_id' => $request->id,
            ]);

            // Update request status
            $request->status = 'approved';
            $request->save();

            return $unlockAccess;
        });
    }

    /**
     * Reject unlock request.
     */
    public function rejectUnlockRequest(int $requestId, int $rejectedBy): InvestmentUnlockRequest
    {
        $request = InvestmentUnlockRequest::findOrFail($requestId);
        
        $request->status = 'rejected';
        $request->save();

        return $request->fresh();
    }
}




