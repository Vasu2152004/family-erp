<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\FinanceAccount;
use App\Services\BudgetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        private BudgetService $budgetService
    ) {
    }

    /**
     * Create a new transaction.
     */
    public function createTransaction(array $data, int $tenantId, int $familyId): Transaction
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            $transaction = Transaction::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'finance_account_id' => $data['finance_account_id'],
                'family_member_id' => $data['family_member_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'is_shared' => $data['is_shared'] ?? false,
                'transfer_to_account_id' => $data['transfer_to_account_id'] ?? null,
                'budget_allocation' => $data['budget_allocation'] ?? null,
                'budget_id' => $data['budget_id'] ?? null,
            ]);

            // Update account balances
            $this->updateAccountBalances($transaction->id);

            // Check budget alerts if this is an expense transaction
            if ($transaction->type === 'EXPENSE') {
                $transactionDate = \Carbon\Carbon::parse($transaction->transaction_date);
                $this->budgetService->checkBudgetAlerts($familyId, $transactionDate->month, $transactionDate->year);
            }

            return $transaction->fresh();
        });
    }

    /**
     * Update an existing transaction.
     */
    public function updateTransaction(int $transactionId, array $data): Transaction
    {
        return DB::transaction(function () use ($transactionId, $data) {
            $transaction = Transaction::findOrFail($transactionId);
            $oldAccountId = $transaction->finance_account_id;
            $oldTransferAccountId = $transaction->transfer_to_account_id;

            $transaction->update([
                'finance_account_id' => $data['finance_account_id'] ?? $transaction->finance_account_id,
                'family_member_id' => $data['family_member_id'] ?? $transaction->family_member_id,
                'category_id' => $data['category_id'] ?? $transaction->category_id,
                'type' => $data['type'] ?? $transaction->type,
                'amount' => $data['amount'] ?? $transaction->amount,
                'description' => $data['description'] ?? $transaction->description,
                'transaction_date' => $data['transaction_date'] ?? $transaction->transaction_date,
                'is_shared' => $data['is_shared'] ?? $transaction->is_shared,
                'transfer_to_account_id' => $data['transfer_to_account_id'] ?? $transaction->transfer_to_account_id,
                'budget_allocation' => $data['budget_allocation'] ?? $transaction->budget_allocation,
                'budget_id' => $data['budget_id'] ?? $transaction->budget_id,
            ]);

            // Update balances for old and new accounts
            $accountsToUpdate = array_unique([
                $oldAccountId,
                $transaction->finance_account_id,
                $oldTransferAccountId,
                $transaction->transfer_to_account_id,
            ]);

            foreach ($accountsToUpdate as $accountId) {
                if ($accountId) {
                    $account = FinanceAccount::find($accountId);
                    if ($account) {
                        $account->updateBalance();
                    }
                }
            }

            // Check budget alerts if this is an expense transaction
            if ($transaction->type === 'EXPENSE') {
                $transactionDate = \Carbon\Carbon::parse($transaction->transaction_date);
                $this->budgetService->checkBudgetAlerts($transaction->family_id, $transactionDate->month, $transactionDate->year);
            }

            return $transaction->fresh();
        });
    }

    /**
     * Delete a transaction.
     */
    public function deleteTransaction(int $transactionId): void
    {
        DB::transaction(function () use ($transactionId) {
            $transaction = Transaction::findOrFail($transactionId);
            $accountId = $transaction->finance_account_id;
            $transferAccountId = $transaction->transfer_to_account_id;
            $familyId = $transaction->family_id;
            $transactionDate = \Carbon\Carbon::parse($transaction->transaction_date);

            $transaction->delete();

            // Update account balances
            if ($accountId) {
                $account = FinanceAccount::find($accountId);
                if ($account) {
                    $account->updateBalance();
                }
            }

            if ($transferAccountId) {
                $account = FinanceAccount::find($transferAccountId);
                if ($account) {
                    $account->updateBalance();
                }
            }

            // Check budget alerts if this was an expense transaction
            if ($transaction->type === 'EXPENSE') {
                $this->budgetService->checkBudgetAlerts($familyId, $transactionDate->month, $transactionDate->year);
            }
        });
    }

    /**
     * Handle a transfer transaction (creates two transactions).
     */
    public function handleTransfer(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Create outgoing transaction
            $outgoingTransaction = $this->createTransaction([
                'finance_account_id' => $data['from_account_id'],
                'family_member_id' => $data['family_member_id'] ?? null,
                'category_id' => null, // Transfers don't have categories
                'type' => 'TRANSFER',
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'Transfer to ' . FinanceAccount::find($data['to_account_id'])->name,
                'transaction_date' => $data['transaction_date'],
                'is_shared' => $data['is_shared'] ?? false,
                'transfer_to_account_id' => $data['to_account_id'],
            ], $data['tenant_id'], $data['family_id']);

            // Note: The incoming transaction is handled automatically by the balance calculation
            // The transfer_to_account_id relationship is used to credit the receiving account

            return $outgoingTransaction;
        });
    }

    /**
     * Update account balances for a transaction.
     */
    public function updateAccountBalances(int $transactionId): void
    {
        $transaction = Transaction::findOrFail($transactionId);

        // Update source account
        if ($transaction->finance_account_id) {
            $account = FinanceAccount::find($transaction->finance_account_id);
            if ($account) {
                $account->updateBalance();
            }
        }

        // Update destination account (for transfers)
        if ($transaction->transfer_to_account_id) {
            $account = FinanceAccount::find($transaction->transfer_to_account_id);
            if ($account) {
                $account->updateBalance();
            }
        }
    }
}
