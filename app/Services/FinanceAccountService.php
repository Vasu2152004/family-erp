<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FinanceAccount;
use Illuminate\Support\Facades\DB;

class FinanceAccountService
{
    /**
     * Create a new finance account.
     */
    public function createAccount(array $data, int $tenantId, int $familyId): FinanceAccount
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            $account = FinanceAccount::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'name' => $data['name'],
                'type' => $data['type'],
                'initial_balance' => $data['initial_balance'] ?? 0,
                'current_balance' => $data['initial_balance'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'description' => $data['description'] ?? null,
            ]);

            return $account;
        });
    }

    /**
     * Update an existing finance account.
     */
    public function updateAccount(int $accountId, array $data): FinanceAccount
    {
        return DB::transaction(function () use ($accountId, $data) {
            $account = FinanceAccount::findOrFail($accountId);

            $account->update([
                'name' => $data['name'] ?? $account->name,
                'type' => $data['type'] ?? $account->type,
                'initial_balance' => $data['initial_balance'] ?? $account->initial_balance,
                'is_active' => $data['is_active'] ?? $account->is_active,
                'description' => $data['description'] ?? $account->description,
            ]);

            // Recalculate balance if initial balance changed
            if (isset($data['initial_balance'])) {
                $this->updateAccountBalance($accountId);
            }

            return $account->fresh();
        });
    }

    /**
     * Delete a finance account.
     */
    public function deleteAccount(int $accountId): void
    {
        DB::transaction(function () use ($accountId) {
            $account = FinanceAccount::findOrFail($accountId);

            // Check if account has transactions
            if ($account->transactions()->count() > 0) {
                throw new \Exception('Cannot delete account with existing transactions.');
            }

            $account->delete();
        });
    }

    /**
     * Recalculate and update account balance from transactions.
     */
    public function updateAccountBalance(int $accountId): void
    {
        $account = FinanceAccount::findOrFail($accountId);
        $account->updateBalance();
    }
}
