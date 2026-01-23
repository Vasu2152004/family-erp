<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class FamilyMemberService
{
    /**
     * Create a family member.
     */
    public function createMember(array $data, int $tenantId, int $familyId): FamilyMember
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            // Check if user is already a member of this family
            if (isset($data['user_id']) && $data['user_id']) {
                $existingMember = FamilyMember::where('family_id', $familyId)
                    ->where('user_id', $data['user_id'])
                    ->first();

                if ($existingMember) {
                    throw ValidationException::withMessages([
                        'user_id' => ['This user is already a member of this family.'],
                    ]);
                }
            }

            return FamilyMember::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'relation' => $data['relation'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'is_deceased' => $data['is_deceased'] ?? false,
                'date_of_death' => $data['date_of_death'] ?? null,
            ]);
        });
    }

    /**
     * Update a family member.
     */
    public function updateMember(int $memberId, array $data): FamilyMember
    {
        return DB::transaction(function () use ($memberId, $data) {
            $member = FamilyMember::findOrFail($memberId);

            // If user_id is being updated, check for duplicates
            if (isset($data['user_id']) && $data['user_id'] && $data['user_id'] !== $member->user_id) {
                $existingMember = FamilyMember::where('family_id', $member->family_id)
                    ->where('user_id', $data['user_id'])
                    ->where('id', '!=', $memberId)
                    ->first();

                if ($existingMember) {
                    throw ValidationException::withMessages([
                        'user_id' => ['This user is already linked to another member in this family.'],
                    ]);
                }
            }

            $member->update([
                'first_name' => $data['first_name'] ?? $member->first_name,
                'last_name' => $data['last_name'] ?? $member->last_name,
                'gender' => $data['gender'] ?? $member->gender,
                'date_of_birth' => $data['date_of_birth'] ?? $member->date_of_birth,
                'relation' => $data['relation'] ?? $member->relation,
                'phone' => $data['phone'] ?? $member->phone,
                'email' => $data['email'] ?? $member->email,
                'is_deceased' => $data['is_deceased'] ?? $member->is_deceased,
                'date_of_death' => $data['date_of_death'] ?? $member->date_of_death,
            ]);

            return $member->fresh();
        });
    }

    /**
     * Link a family member to a system user.
     */
    public function linkToUser(int $memberId, int $userId): FamilyMember
    {
        return DB::transaction(function () use ($memberId, $userId) {
            $member = FamilyMember::findOrFail($memberId);

            // Check if user is already linked to another member in this family
            $existingMember = FamilyMember::where('family_id', $member->family_id)
                ->where('user_id', $userId)
                ->where('id', '!=', $memberId)
                ->first();

            if ($existingMember) {
                throw ValidationException::withMessages([
                    'user_id' => ['This user is already linked to another member in this family.'],
                ]);
            }

            $member->update([
                'user_id' => $userId,
            ]);

            return $member->fresh();
        });
    }

}
