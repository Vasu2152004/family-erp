<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ShoppingListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'inventory_item_id',
        'name',
        'qty',
        'unit',
        'notes',
        'added_by',
        'purchased_by',
        'purchased_at',
        'is_purchased',
        'is_auto_added',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'is_purchased' => 'boolean',
            'is_auto_added' => 'boolean',
            'purchased_at' => 'datetime',
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function purchasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    /**
     * Scope to filter items by tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter items by family.
     */
    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }

    /**
     * Scope to get purchased items.
     */
    public function scopePurchased(Builder $query): Builder
    {
        return $query->where('is_purchased', true);
    }

    /**
     * Scope to get pending items.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_purchased', false);
    }

    /**
     * Scope to get auto-added items.
     */
    public function scopeAutoAdded(Builder $query): Builder
    {
        return $query->where('is_auto_added', true);
    }

    /**
     * Mark item as purchased.
     */
    public function markAsPurchased(?int $userId = null): self
    {
        $this->update([
            'is_purchased' => true,
            'purchased_by' => $userId ?? Auth::id(),
            'purchased_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark item as pending.
     */
    public function markAsPending(): self
    {
        $this->update([
            'is_purchased' => false,
            'purchased_by' => null,
            'purchased_at' => null,
        ]);

        return $this;
    }
}
