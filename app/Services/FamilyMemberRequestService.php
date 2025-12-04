<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FamilyMemberRequest;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyMemberRequestService
{
    public function __construct(
        private FamilyMemberService $familyMemberService
    ) {
    }

    /**
     * Create a request to add a family member.
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
                    'user_id' => ['This user is already a member of this family.'],
                ]);
            }

            // Check if there's already a pending request
            $existingRequest = FamilyMemberRequest::where('family_id', $familyId)
                ->where('requested_user_id', $requestedUserId)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                throw ValidationException::withMessages([
                    'user_id' => ['There is already a pending request for this user.'],
                ]);
            }

            return FamilyMemberRequest::create([
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
        });
    }

    /**
     * Accept a family member request.
     */
    public function acceptRequest(int $requestId, int $userId): FamilyMember
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $request = FamilyMemberRequest::findOrFail($requestId);

            // Verify the user is the one who should accept
            if ($request->requested_user_id !== $userId) {
                throw ValidationException::withMessages([
                    'request' => ['You are not authorized to accept this request.'],
                ]);
            }

            // Verify request is still pending
            if (!$request->isPending()) {
                throw ValidationException::withMessages([
                    'request' => ['This request has already been ' . $request->status . '.'],
                ]);
            }

            // Create the family member
            $member = $this->familyMemberService->createMember(
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'gender' => $request->gender,
                    'date_of_birth' => $request->date_of_birth,
                    'relation' => $request->relation,
                    'phone' => $request->phone,
                    'user_id' => $request->requested_user_id,
                ],
                $request->tenant_id,
                $request->family_id
            );

            // Update request status
            $request->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            return $member;
        });
    }

    /**
     * Reject a family member request.
     */
    public function rejectRequest(int $requestId, int $userId): FamilyMemberRequest
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $request = FamilyMemberRequest::findOrFail($requestId);

            // Verify the user is the one who should reject
            if ($request->requested_user_id !== $userId) {
                throw ValidationException::withMessages([
                    'request' => ['You are not authorized to reject this request.'],
                ]);
            }

            // Verify request is still pending
            if (!$request->isPending()) {
                throw ValidationException::withMessages([
                    'request' => ['This request has already been ' . $request->status . '.'],
                ]);
            }

            // Update request status
            $request->update([
                'status' => 'rejected',
                'responded_at' => now(),
            ]);

            return $request;
        });
    }
}

