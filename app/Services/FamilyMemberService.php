<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\FamilyMemberDeceased;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyMemberService
{
    /**
     * Create a new family member.
     * Requires an existing user (either via user_id or email lookup).
     * 
     * @throws \Illuminate\Validation\ValidationException if user not found
     */
    public function createMember(array $data, int $tenantId, int $familyId): FamilyMember
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            $userId = $data['user_id'] ?? null;
            $email = $data['email'] ?? null;

            // If user_id not provided, try to find user by email (across all tenants)
            if (!$userId && $email) {
                $user = \App\Models\User::where('email', $email)->first();

                if ($user) {
                    $userId = $user->id;
                } else {
                    throw ValidationException::withMessages([
                        'email' => ['No user account found with this email. The user must exist in the system before adding as a family member.'],
                    ]);
                }
            }

            // User ID is required - throw error if still not found
            if (!$userId) {
                throw ValidationException::withMessages([
                    'user_id' => ['Please select a user or provide an email of an existing user.'],
                ]);
            }

            // Verify user exists (allow cross-tenant users)
            $user = \App\Models\User::findOrFail($userId);

            // Check if user is already a member of this family
            $existingMember = FamilyMember::where('user_id', $userId)
                ->where('family_id', $familyId)
                ->first();

            if ($existingMember) {
                throw ValidationException::withMessages([
                    'user_id' => ['This user is already a member of this family.'],
                ]);
            }

            $member = FamilyMember::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'relation' => $data['relation'],
                'phone' => $data['phone'] ?? null,
                'email' => $user->email ?? $email,
                'is_deceased' => false,
                'user_id' => $userId,
            ]);

            return $member;
        });
    }

    /**
     * Update a family member.
     */
    public function updateMember(int $memberId, array $data): FamilyMember
    {
        return DB::transaction(function () use ($memberId, $data) {
            $member = FamilyMember::findOrFail($memberId);
            $wasDeceased = $member->is_deceased;
            $wasOwner = $member->user_id && $member->user?->isFamilyOwner($member->family_id);

            // If email is updated and no user_id is set, try to find and link user
            if (isset($data['email']) && !isset($data['user_id']) && !$member->user_id) {
                $user = \App\Models\User::where('email', $data['email'])
                    ->where('tenant_id', $member->tenant_id)
                    ->first();

                if ($user) {
                    $data['user_id'] = $user->id;
                }
            }

            $member->update($data);

            if (!$wasDeceased && ($data['is_deceased'] ?? false)) {
                event(new FamilyMemberDeceased($member->fresh(), $wasOwner));
            }

            return $member->fresh();
        });
    }

    /**
     * Link a family member to a system user.
     */
    public function linkToUser(int $memberId, int $userId): FamilyMember
    {
        $member = FamilyMember::findOrFail($memberId);
        $member->update(['user_id' => $userId]);
        return $member->fresh();
    }

    /**
     * Mark a family member as deceased.
     */
    public function markAsDeceased(int $memberId, ?string $dateOfDeath = null): FamilyMember
    {
        return DB::transaction(function () use ($memberId, $dateOfDeath) {
            $member = FamilyMember::findOrFail($memberId);
            $wasOwner = $member->user_id && $member->user?->isFamilyOwner($member->family_id);

            $member->update([
                'is_deceased' => true,
                'date_of_death' => $dateOfDeath ?? now()->toDateString(),
            ]);

            event(new FamilyMemberDeceased($member->fresh(), $wasOwner));

            return $member->fresh();
        });
    }
}
