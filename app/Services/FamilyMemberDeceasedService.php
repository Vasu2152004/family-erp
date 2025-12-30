<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyMemberDeceasedVote;
use App\Models\FamilyUserRole;
use App\Models\Notification as AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyMemberDeceasedService
{
    /**
     * Start a deceased verification voting round.
     */
    public function startVerification(FamilyMember $member, User $requester, ?string $dateOfDeath = null): void
    {
        if ($member->is_deceased) {
            throw ValidationException::withMessages(['member' => 'Member is already marked as deceased.']);
        }

        DB::transaction(function () use ($member, $requester, $dateOfDeath) {
            // Reset pending flags and existing votes
            FamilyMemberDeceasedVote::where('family_member_id', $member->id)->delete();

            $member->update([
                'is_deceased' => false,
                'is_deceased_pending' => true,
                'date_of_death' => $dateOfDeath ?: $member->date_of_death,
            ]);

            // Count total family members (including owners)
            $totalMembers = $this->countTotalFamilyMembers($member->family_id);
            
            // Exclude the deceased person themselves from voting
            $excludeUserId = $member->user_id;
            $voterIds = $this->resolveVoters($member->family_id, $excludeUserId);

            if (empty($voterIds)) {
                throw ValidationException::withMessages(['member' => 'No eligible voters found for this family.']);
            }

            // Calculate required votes: X-1 (where X is total members)
            $requiredVotes = max(1, $totalMembers - 1);

            foreach ($voterIds as $voterId) {
                FamilyMemberDeceasedVote::create([
                    'family_member_id' => $member->id,
                    'user_id' => $voterId,
                    'requested_by' => $requester->id,
                    'status' => 'pending',
                ]);
            }

            // Store required votes count in member's data or use a separate approach
            // We'll check this in castVote method
            $this->notifyStart($member, $requester, $voterIds, $requiredVotes);
        });
    }

    /**
     * Cast a vote (approve/deny) for the current user.
     */
    public function castVote(FamilyMember $member, User $user, string $decision): void
    {
        if (!$member->is_deceased_pending) {
            throw ValidationException::withMessages(['member' => 'No pending deceased verification for this member.']);
        }

        if (!in_array($decision, ['approved', 'denied'], true)) {
            throw ValidationException::withMessages(['decision' => 'Invalid decision.']);
        }

        // Retry logic for deadlock handling
        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                DB::transaction(function () use ($member, $user, $decision) {
                    // Lock the member row to prevent concurrent updates
                    $member = FamilyMember::lockForUpdate()->findOrFail($member->id);

                    if (!$member->is_deceased_pending) {
                        throw ValidationException::withMessages(['member' => 'No pending deceased verification for this member.']);
                    }

                    $vote = FamilyMemberDeceasedVote::where('family_member_id', $member->id)
                        ->where('user_id', $user->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$vote) {
                        throw ValidationException::withMessages(['vote' => 'You are not eligible to vote on this member.']);
                    }

                    if ($vote->status !== 'pending') {
                        // Idempotent: ignore repeat votes
                        return;
                    }

                    $vote->update(['status' => $decision]);

                    // If any deny, stop and fail
                    $hasDenied = FamilyMemberDeceasedVote::where('family_member_id', $member->id)
                        ->where('status', 'denied')
                        ->exists();

                    if ($hasDenied) {
                        $member->update([
                            'is_deceased_pending' => false,
                            'is_deceased' => false,
                        ]);

                        $this->notifyResult($member, 'denied');
                        return;
                    }

                    // Count total family members (X) and calculate required votes (X-1)
                    $totalMembers = $this->countTotalFamilyMembers($member->family_id);
                    $requiredApprovedVotes = max(1, $totalMembers - 1);
                    
                    // Count approved votes
                    $approvedCount = FamilyMemberDeceasedVote::where('family_member_id', $member->id)
                        ->where('status', 'approved')
                        ->count();

                    // If X-1 votes are approved, mark as deceased
                    if ($approvedCount >= $requiredApprovedVotes) {
                        // Use direct DB update to avoid deadlock
                        DB::table('family_members')
                            ->where('id', $member->id)
                            ->update([
                                'is_deceased_pending' => false,
                                'is_deceased' => true,
                                'date_of_death' => $member->date_of_death ?: now()->toDateString(),
                                'updated_at' => now(),
                            ]);

                        // Refresh member instance
                        $member->refresh();

                        // Unlock hidden investments and locked assets for this deceased member
                        $this->unlockInvestmentsAndAssets($member);

                        $this->notifyResult($member, 'approved');
                    }
                }, 3); // 3 attempts for deadlock retry

                // Success - break out of retry loop
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                // Check if it's a deadlock error
                if ($e->getCode() == 40001 || str_contains($e->getMessage(), 'Deadlock')) {
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        throw $e;
                    }
                    // Wait a random amount of time (0-100ms) before retrying
                    usleep(rand(0, 100000));
                    continue;
                }
                // Not a deadlock, rethrow
                throw $e;
            }
        }
    }

    /**
     * Get aggregated vote counts.
     */
    public function counts(FamilyMember $member): array
    {
        $totals = FamilyMemberDeceasedVote::where('family_member_id', $member->id)
            ->selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'pending' => $totals['pending'] ?? 0,
            'approved' => $totals['approved'] ?? 0,
            'denied' => $totals['denied'] ?? 0,
            'total' => array_sum($totals),
        ];
    }

    /**
     * Resolve eligible voters: users linked to this family via roles or member linkage.
     * Excludes the member being voted on (if they have a user_id).
     */
    private function resolveVoters(int $familyId, ?int $excludeUserId = null): array
    {
        $roleUserIds = FamilyUserRole::where('family_id', $familyId)
            ->when($excludeUserId, fn($q) => $q->where('user_id', '!=', $excludeUserId))
            ->pluck('user_id')
            ->toArray();
        
        $memberUserIds = Family::find($familyId)?->members()
            ->whereNotNull('user_id')
            ->when($excludeUserId, fn($q) => $q->where('user_id', '!=', $excludeUserId))
            ->pluck('user_id')
            ->toArray() ?? [];

        return array_values(array_unique(array_filter(array_merge($roleUserIds, $memberUserIds))));
    }

    /**
     * Count total family members including owners.
     * This counts all family members (with or without user_id) plus owners who aren't already members.
     */
    private function countTotalFamilyMembers(int $familyId): int
    {
        // Count all family members (regardless of user_id)
        $memberCount = FamilyMember::where('family_id', $familyId)->count();
        
        // Get user IDs of all members
        $memberUserIds = FamilyMember::where('family_id', $familyId)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->toArray();
        
        // Get owner user IDs who are NOT already members
        $ownerUserIds = FamilyUserRole::where('family_id', $familyId)
            ->where('role', 'OWNER')
            ->whereNotIn('user_id', $memberUserIds)
            ->pluck('user_id')
            ->toArray();
        
        // Total = members + owners who aren't members
        return $memberCount + count($ownerUserIds);
    }

    private function notifyStart(FamilyMember $member, User $requester, array $voterIds, int $requiredVotes = null): void
    {
        $tenantId = $this->resolveTenantId($member);
        $message = $requiredVotes 
            ? "{$requester->name} started a deceased verification for {$member->first_name} {$member->last_name}. {$requiredVotes} approval vote(s) required. Please vote."
            : "{$requester->name} started a deceased verification for {$member->first_name} {$member->last_name}. Please vote.";
        
        foreach ($voterIds as $voterId) {
            $this->sendNotification(
                $tenantId,
                $voterId,
                'deceased_verification_started',
                'Deceased verification started',
                $message,
                [
                    'family_id' => $member->family_id,
                    'family_member_id' => $member->id,
                    'requested_by' => $requester->id,
                    'required_votes' => $requiredVotes,
                ]
            );
        }
    }

    private function notifyResult(FamilyMember $member, string $result): void
    {
        $tenantId = $this->resolveTenantId($member);
        $voterIds = $this->resolveVoters($member->family_id);
        $statusText = $result === 'approved'
            ? 'All votes approved. Member marked as deceased.'
            : 'A vote was denied. Deceased request failed.';

        foreach ($voterIds as $voterId) {
            $this->sendNotification(
                $tenantId,
                $voterId,
                'deceased_verification_result',
                'Deceased verification ' . $result,
                "{$member->first_name} {$member->last_name}: {$statusText}",
                [
                    'family_id' => $member->family_id,
                    'family_member_id' => $member->id,
                    'result' => $result,
                ]
            );
        }
    }

    private function sendNotification(int $tenantId, int $userId, string $type, string $title, string $message, array $data = []): void
    {
        AppNotification::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function resolveTenantId(FamilyMember $member): int
    {
        return $member->family?->tenant_id ?? Family::findOrFail($member->family_id)->tenant_id;
    }

    /**
     * Unlock hidden investments and locked assets when owner is marked as deceased.
     * Note: This should be called within an existing transaction.
     */
    private function unlockInvestmentsAndAssets(FamilyMember $member): void
    {
        // Unlock all hidden investments owned by this member
        \App\Models\Investment::where('family_id', $member->family_id)
            ->where('family_member_id', $member->id)
            ->where('is_hidden', true)
            ->update([
                'is_hidden' => false,
                'pin_hash' => null, // Remove PIN protection
                'updated_at' => now(),
            ]);

        // Unlock all locked assets owned by this member
        \App\Models\Asset::where('family_id', $member->family_id)
            ->where('family_member_id', $member->id)
            ->where('is_locked', true)
            ->update([
                'is_locked' => false,
                'pin_hash' => null, // Remove PIN protection
                'updated_at' => now(),
            ]);
    }
}
