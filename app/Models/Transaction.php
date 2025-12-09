<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'finance_account_id',
        'family_member_id',
        'category_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'is_shared',
        'transfer_to_account_id',
        'budget_allocation',
        'budget_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'is_shared' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function transferToAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'transfer_to_account_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Scope to filter transactions by tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by transaction type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by family member.
     */
    public function scopeByMember(Builder $query, int $memberId): Builder
    {
        return $query->where('family_member_id', $memberId);
    }

    /**
     * Scope to get only shared transactions.
     */
    public function scopeShared(Builder $query): Builder
    {
        return $query->where('is_shared', true);
    }

    /**
     * Scope to get only private transactions.
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_shared', false);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Check if transaction is income.
     */
    public function isIncome(): bool
    {
        return $this->type === 'INCOME';
    }

    /**
     * Check if transaction is expense.
     */
    public function isExpense(): bool
    {
        return $this->type === 'EXPENSE';
    }

    /**
     * Check if transaction is transfer.
     */
    public function isTransfer(): bool
    {
        return $this->type === 'TRANSFER';
    }

    /**
     * Check if user can view this transaction.
     */
    public function canView(User $user): bool
    {
        // OWNER/ADMIN can view all
        $userRole = \App\Models\FamilyUserRole::where('family_id', $this->family_id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            return true;
        }

        // Check if user is a family member (with or without role)
        $isFamilyMember = \App\Models\FamilyMember::where('family_id', $this->family_id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isFamilyMember && !$userRole) {
            return false;
        }

        // MEMBER or family member can view if it's their transaction or if it's shared
        if ($this->family_member_id) {
            $member = FamilyMember::find($this->family_member_id);
            if ($member && $member->user_id === $user->id) {
                return true;
            }
        }

        return $this->is_shared;
    }
}
