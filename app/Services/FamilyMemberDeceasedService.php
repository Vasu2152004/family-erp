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

            $voterIds = $this->resolveVoters($member->family_id);

            if (empty($voterIds)) {
                throw ValidationException::withMessages(['member' => 'No eligible voters found for this family.']);
            }

            foreach ($voterIds as $voterId) {
                FamilyMemberDeceasedVote::create([
                    'family_member_id' => $member->id,
                    'user_id' => $voterId,
                    'requested_by' => $requester->id,
                    'status' => 'pending',
                ]);
            }

            $this->notifyStart($member, $requester, $voterIds);
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

        DB::transaction(function () use ($member, $user, $decision) {
            $vote = FamilyMemberDeceasedVote::where('family_member_id', $member->id)
                ->where('user_id', $user->id)
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

            // If all approved, mark deceased
            $pendingLeft = FamilyMemberDeceasedVote::where('family_member_id', $member->id)
                ->where('status', 'pending')
                ->exists();

            if (!$pendingLeft) {
                $member->update([
                    'is_deceased_pending' => false,
                    'is_deceased' => true,
                    'date_of_death' => $member->date_of_death ?: now()->toDateString(),
                ]);

                $this->notifyResult($member, 'approved');
            }
        });
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
     */
    private function resolveVoters(int $familyId): array
    {
        $roleUserIds = FamilyUserRole::where('family_id', $familyId)->pluck('user_id')->toArray();
        $memberUserIds = Family::find($familyId)?->members()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->toArray() ?? [];

        return array_values(array_unique(array_filter(array_merge($roleUserIds, $memberUserIds))));
    }

    private function notifyStart(FamilyMember $member, User $requester, array $voterIds): void
    {
        $tenantId = $this->resolveTenantId($member);
        foreach ($voterIds as $voterId) {
            $this->sendNotification(
                $tenantId,
                $voterId,
                'deceased_verification_started',
                'Deceased verification started',
                "{$requester->name} started a deceased verification for {$member->first_name} {$member->last_name}. Please vote.",
                [
                    'family_id' => $member->family_id,
                    'family_member_id' => $member->id,
                    'requested_by' => $requester->id,
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
}
