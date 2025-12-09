<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'category_id',
        'month',
        'year',
        'amount',
        'alert_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'alert_threshold' => 'decimal:2',
            'is_active' => 'boolean',
            'month' => 'integer',
            'year' => 'integer',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    /**
     * Check if this is a personal budget (has family_member_id).
     */
    public function isPersonal(): bool
    {
        return $this->family_member_id !== null;
    }

    /**
     * Check if this is a family budget (no family_member_id).
     */
    public function isFamily(): bool
    {
        return $this->family_member_id === null;
    }

    /**
     * Scope to filter budgets by tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get only active budgets.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by month.
     */
    public function scopeByMonth(Builder $query, int $month): Builder
    {
        return $query->where('month', $month);
    }

    /**
     * Scope to filter by year.
     */
    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    /**
     * Get the amount spent for this budget.
     * Only counts EXPENSE transactions that are directly assigned to this budget.
     */
    public function getSpentAmount(): float
    {
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        $query = Transaction::where('family_id', $this->family_id)
            ->where('type', 'EXPENSE') // Only expenses count towards budget
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // If budget_id column exists, use it for direct assignment
        if (Schema::hasColumn('transactions', 'budget_id')) {
            // Only count transactions directly assigned to this specific budget
            $query->where('budget_id', $this->id);
        } else {
            // Fallback: use budget_allocation logic if budget_id doesn't exist yet
            if (Schema::hasColumn('transactions', 'budget_allocation')) {
                if ($this->isPersonal()) {
                    // Personal budget: count transactions with budget_allocation = 'personal' or 'both'
                    // AND that belong to this specific member
                    $query->where(function ($q) {
                        $q->where('budget_allocation', 'personal')
                          ->orWhere('budget_allocation', 'both');
                    });
                    
                    // Must belong to this specific member
                    $query->where('family_member_id', $this->family_member_id);
                } else {
                    // Family budget: count transactions with budget_allocation = 'family' or 'both'
                    $query->where(function ($q) {
                        $q->where('budget_allocation', 'family')
                          ->orWhere('budget_allocation', 'both');
                    });
                }
            } else {
                // Final fallback: if neither column exists
                if ($this->isPersonal()) {
                    $query->where('family_member_id', $this->family_member_id);
                }
            }
        }

        // Filter by category if specified
        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get the remaining budget amount.
     */
    public function getRemainingAmount(): float
    {
        return max(0, $this->amount - $this->getSpentAmount());
    }

    /**
     * Check if budget is exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->getSpentAmount() > $this->amount;
    }

    /**
     * Get the alert threshold amount.
     */
    public function getAlertThreshold(): float
    {
        if (!$this->alert_threshold) {
            return 0;
        }

        return $this->amount * ($this->alert_threshold / 100);
    }

    /**
     * Check if budget has reached alert threshold.
     */
    public function hasReachedAlertThreshold(): bool
    {
        if (!$this->alert_threshold) {
            return false;
        }

        return $this->getSpentAmount() >= $this->getAlertThreshold();
    }
}
