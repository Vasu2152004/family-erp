<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class FamilyMemberService
{
    /**
     * Ensure all family owners have FamilyMember records so they appear in member selection lists.
     * Call this before loading members for dropdowns/filters. Idempotent.
     */
    public function ensureOwnersHaveFamilyMembers(Family $family): void
    {
        $ownerRoles = $family->roles()->where('role', 'OWNER')->with('user')->get();
        foreach ($ownerRoles as $role) {
            if ($role->user) {
                $this->ensureOwnerFamilyMember($family, $role->user);
            }
        }
    }

    /**
     * Get family members for selection dropdowns/filters.
     * Owners are included via migration (ensure_all_owners_have_family_members). No runtime ensure.
     *
     * @param  bool  $aliveOnly  When true, excludes deceased members (for forms like doctor visits, tasks).
     */
    public function getMembersForSelection(Family $family, bool $aliveOnly = false): \Illuminate\Support\Collection
    {
        $query = $family->members()->with('user:id,name,email')->orderBy('first_name')->orderBy('last_name');
        if ($aliveOnly) {
            $query->alive();
        }
        return $query->get();
    }

    /**
     * Ensure a family owner has a FamilyMember record so they appear in member selection lists.
     * Creates one if missing. Idempotent - safe to call multiple times.
     * Note: user_id is unique globally in family_members - if user already has a record in another family, we skip.
     */
    public function ensureOwnerFamilyMember(Family $family, User $user): ?FamilyMember
    {
        $existing = FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // User can only have one FamilyMember globally (user_id unique) - skip if linked elsewhere
        if (FamilyMember::where('user_id', $user->id)->where('family_id', '!=', $family->id)->exists()) {
            return null;
        }

        // Link existing unlinked member with same email (prevents duplicate entries)
        $unlinkedSameEmail = FamilyMember::where('family_id', $family->id)
            ->whereNull('user_id')
            ->where('email', $user->email)
            ->first();

        if ($unlinkedSameEmail) {
            $unlinkedSameEmail->update([
                'user_id' => $user->id,
                'relation' => 'Owner',
                'first_name' => preg_split('/\s+/', trim($user->name), 2)[0] ?? 'Owner',
                'last_name' => preg_split('/\s+/', trim($user->name), 2)[1] ?? '',
            ]);
            return $unlinkedSameEmail->fresh();
        }

        $nameParts = preg_split('/\s+/', trim($user->name), 2);
        $firstName = $nameParts[0] ?? 'Owner';
        $lastName = $nameParts[1] ?? '';

        try {
            return FamilyMember::create([
                'tenant_id' => $family->tenant_id,
                'family_id' => $family->id,
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => 'other',
                'relation' => 'Owner',
                'email' => $user->email,
                'phone' => null,
                'is_deceased' => false,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return null;
        }
    }

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
