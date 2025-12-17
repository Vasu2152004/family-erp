<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Investment;
use App\Models\InvestmentAccessLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class InvestmentService
{
    /**
     * Create a new investment.
     */
    public function createInvestment(array $data, int $tenantId, int $familyId): Investment
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            $investment = Investment::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $data['family_member_id'] ?? null,
                'investment_type' => $data['investment_type'] ?? 'OTHER',
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'] ?? 0,
                'current_value' => $data['current_value'] ?? null,
                'is_hidden' => $data['is_hidden'] ?? false,
                'details' => $data['details'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            // If hidden, encrypt details and set PIN
            if ($investment->is_hidden) {
                if (!empty($data['pin'])) {
                    $investment->setPinHash($data['pin']);
                }
                if (!empty($data['details'])) {
                    $investment->encryptDetails();
                    $investment->save();
                }
            }

            return $investment->fresh();
        });
    }

    /**
     * Update an existing investment.
     */
    public function updateInvestment(int $investmentId, array $data): Investment
    {
        return DB::transaction(function () use ($investmentId, $data) {
            $investment = Investment::findOrFail($investmentId);

            $investment->update([
                'family_member_id' => $data['family_member_id'] ?? $investment->family_member_id,
                'investment_type' => $data['investment_type'] ?? $investment->investment_type,
                'name' => $data['name'] ?? $investment->name,
                'description' => $data['description'] ?? $investment->description,
                'amount' => $data['amount'] ?? $investment->amount,
                'current_value' => $data['current_value'] ?? $investment->current_value,
                'updated_by' => auth()->id(),
            ]);

            // Update details if provided
            if (isset($data['details'])) {
                if ($investment->is_hidden) {
                    $investment->encrypted_details = Crypt::encryptString($data['details']);
                    $investment->details = null;
                } else {
                    $investment->details = $data['details'];
                    $investment->encrypted_details = null;
                }
                $investment->save();
            }

            return $investment->fresh();
        });
    }

    /**
     * Delete an investment.
     */
    public function deleteInvestment(int $investmentId): void
    {
        DB::transaction(function () use ($investmentId) {
            $investment = Investment::findOrFail($investmentId);
            $investment->delete();
        });
    }

    /**
     * Toggle hidden status of investment.
     */
    public function toggleHidden(int $investmentId, bool $isHidden, ?string $pin = null): Investment
    {
        return DB::transaction(function () use ($investmentId, $isHidden, $pin) {
            $investment = Investment::findOrFail($investmentId);

            if ($isHidden) {
                // Hiding: encrypt details, set PIN
                if (!empty($investment->details)) {
                    $investment->encryptDetails();
                }
                if ($pin) {
                    $investment->setPinHash($pin);
                }
                $investment->is_hidden = true;
            } else {
                // Unhiding: decrypt details, clear PIN
                if (!empty($investment->encrypted_details)) {
                    $investment->decryptDetails();
                    $investment->encrypted_details = null;
                }
                $investment->pin_hash = null;
                $investment->is_hidden = false;
            }

            $investment->save();
            return $investment->fresh();
        });
    }

    /**
     * Unlock investment with PIN verification.
     */
    public function unlockInvestment(int $investmentId, User $user, ?string $pin = null): Investment
    {
        return DB::transaction(function () use ($investmentId, $user, $pin) {
            $investment = Investment::findOrFail($investmentId);

            if (!$investment->is_hidden) {
                return $investment;
            }

            // Verify PIN if provided
            if ($pin && !$investment->verifyPin($pin)) {
                throw new \InvalidArgumentException('Invalid PIN provided.');
            }

            // Log successful unlock
            $this->logAccess($investmentId, $user, 'unlock_success');

            return $investment->fresh();
        });
    }

    /**
     * Log access to investment.
     */
    public function logAccess(int $investmentId, User $user, string $action, array $metadata = []): InvestmentAccessLog
    {
        $investment = Investment::findOrFail($investmentId);

        return InvestmentAccessLog::create([
            'tenant_id' => $investment->tenant_id,
            'family_id' => $investment->family_id,
            'investment_id' => $investmentId,
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $metadata['ip_address'] ?? request()->ip(),
            'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
            'notes' => $metadata['notes'] ?? null,
        ]);
    }

    /**
     * Check if user can access investment.
     */
    public function canUserAccess(Investment $investment, User $user): bool
    {
        // If not hidden, anyone with view permission can access
        if (!$investment->is_hidden) {
            return true;
        }

        // Check if user has permanent unlock access
        if ($investment->isUnlockedFor($user)) {
            return true;
        }

        // Check if user is the owner
        if ($investment->familyMember && $investment->familyMember->user_id === $user->id) {
            return true;
        }

        // Check if user is ADMIN/OWNER and owner is deceased
        if ($investment->canBeRequestedForUnlock($user)) {
            return false; // Can request but doesn't have direct access yet
        }

        return false;
    }
}

