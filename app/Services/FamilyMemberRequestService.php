<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FamilyMemberRequest;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Notifications\FamilyMemberRequestAcceptedNotification;
use App\Notifications\FamilyMemberRequestNotification;
use App\Notifications\FamilyMemberRequestRejectedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyMemberRequestService
{
    public function __construct(
        private FamilyMemberService $familyMemberService
    ) {
    }

    /**
     * Create a family member request.
     */
    public function createRequest(array $data, int $tenantId, int $familyId, int $requestedByUserId, int $requestedUserId): FamilyMemberRequest
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $requestedByUserId, $requestedUserId) {
            // Check if user is already a member of this family
            $existingMember = FamilyMember::where('family_id', $familyId)
                ->where('user_id', $requestedUserId)
                ->first();

            if ($existingMember) {
                throw ValidationException::withMessages([
                    'email' => ['This user is already a member of this family.'],
                ]);
            }

            // Check if user is already a member of ANY other family
            $existingMemberInOtherFamily = FamilyMember::where('user_id', $requestedUserId)
                ->where('family_id', '!=', $familyId)
                ->first();

            if ($existingMemberInOtherFamily) {
                // Get the family name for better error message
                $existingFamily = $existingMemberInOtherFamily->family;
                $familyName = $existingFamily ? $existingFamily->name : 'another family';
                
                throw ValidationException::withMessages([
                    'email' => ["This user is already a member of {$familyName}. A user can only be part of one family at a time. The user must leave their current family before joining a new one."],
                ]);
            }

            // Check if there's already a pending request for this user and family
            $existingRequest = FamilyMemberRequest::where('family_id', $familyId)
                ->where('requested_user_id', $requestedUserId)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                throw ValidationException::withMessages([
                    'email' => ['A pending request already exists for this user in this family.'],
                ]);
            }

            $request = FamilyMemberRequest::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'requested_by_user_id' => $requestedByUserId,
                'requested_user_id' => $requestedUserId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'relation' => $data['relation'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'status' => 'pending',
            ]);

            // Send email notification to the requested user
            $requestedUser = User::findOrFail($requestedUserId);
            $requestedUser->notify(new FamilyMemberRequestNotification($request));

            return $request;
        });
    }

    /**
     * Accept a family member request.
     */
    public function acceptRequest(int $requestId, int $userId): FamilyMember
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $request = FamilyMemberRequest::findOrFail($requestId);

            // Verify the user accepting is the requested user
            if ($request->requested_user_id !== $userId) {
                throw ValidationException::withMessages([
                    'request' => ['You can only accept requests sent to you.'],
                ]);
            }

            // Check if request is still pending
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => ['This request has already been processed.'],
                ]);
            }

            // Check if user is already a member of this specific family
            $existingMember = FamilyMember::where('family_id', $request->family_id)
                ->where('user_id', $userId)
                ->first();

            if ($existingMember) {
                // Update request status even if member already exists
                $request->update([
                    'status' => 'accepted',
                    'responded_at' => now(),
                ]);
                
                throw ValidationException::withMessages([
                    'request' => ['You are already a member of this family.'],
                ]);
            }

            // Check if user is already a member of ANY other family
            $existingMemberInOtherFamily = FamilyMember::where('user_id', $userId)
                ->where('family_id', '!=', $request->family_id)
                ->first();

            if ($existingMemberInOtherFamily) {
                // Get the family name for better error message
                $existingFamily = $existingMemberInOtherFamily->family;
                $familyName = $existingFamily ? $existingFamily->name : 'another family';
                
                throw ValidationException::withMessages([
                    'request' => ["You are already a member of {$familyName}. A user can only be part of one family at a time. Please leave your current family before joining a new one."],
                ]);
            }

            // Create family member from request data
            $member = $this->familyMemberService->createMember([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'relation' => $request->relation,
                'phone' => $request->phone,
                'email' => $request->email,
                'user_id' => $userId,
            ], $request->tenant_id, $request->family_id);

            // Update request status
            $request->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            // Send email notification to the family admin/owner who created the request
            $this->notifyRequestCreator($request, 'accepted');

            return $member;
        });
    }

    /**
     * Reject a family member request.
     */
    public function rejectRequest(int $requestId, int $userId): void
    {
        DB::transaction(function () use ($requestId, $userId) {
            $request = FamilyMemberRequest::findOrFail($requestId);

            // Verify the user rejecting is the requested user
            if ($request->requested_user_id !== $userId) {
                throw ValidationException::withMessages([
                    'request' => ['You can only reject requests sent to you.'],
                ]);
            }

            // Check if request is still pending
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => ['This request has already been processed.'],
                ]);
            }

            // Update request status
            $request->update([
                'status' => 'rejected',
                'responded_at' => now(),
            ]);

            // Send email notification to the family admin/owner who created the request
            $this->notifyRequestCreator($request, 'rejected');
        });
    }

    /**
     * Notify the family admin/owner who created the request about accept/reject.
     */
    private function notifyRequestCreator(FamilyMemberRequest $request, string $status): void
    {
        // Get the user who created the request
        $requestCreator = $request->requestedBy;
        
        if ($requestCreator) {
            if ($status === 'accepted') {
                $requestCreator->notify(new FamilyMemberRequestAcceptedNotification($request));
            } else {
                $requestCreator->notify(new FamilyMemberRequestRejectedNotification($request));
            }
        }
    }
}
