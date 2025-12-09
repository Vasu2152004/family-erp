<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class FinanceAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'name',
        'type',
        'initial_balance',
        'current_balance',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transferToTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_to_account_id');
    }

    /**
     * Scope to filter accounts by tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get only active accounts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by account type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Recalculate and update account balance from transactions.
     */
    public function updateBalance(): void
    {
        $balance = $this->initial_balance;

        // Add income transactions
        $balance += $this->transactions()
            ->where('type', 'INCOME')
            ->sum('amount');

        // Subtract expense transactions
        $balance -= $this->transactions()
            ->where('type', 'EXPENSE')
            ->sum('amount');

        // Subtract outgoing transfers
        $balance -= $this->transactions()
            ->where('type', 'TRANSFER')
            ->sum('amount');

        // Add incoming transfers
        $balance += Transaction::where('transfer_to_account_id', $this->id)
            ->where('type', 'TRANSFER')
            ->sum('amount');

        $this->update(['current_balance' => $balance]);
    }

    /**
     * Calculate account balance without saving.
     */
    public function calculateBalance(): float
    {
        $balance = $this->initial_balance;

        $balance += $this->transactions()
            ->where('type', 'INCOME')
            ->sum('amount');

        $balance -= $this->transactions()
            ->where('type', 'EXPENSE')
            ->sum('amount');

        $balance -= $this->transactions()
            ->where('type', 'TRANSFER')
            ->sum('amount');

        $balance += Transaction::where('transfer_to_account_id', $this->id)
            ->where('type', 'TRANSFER')
            ->sum('amount');

        return (float) $balance;
    }
}
