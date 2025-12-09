<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'category_id',
        'name',
        'qty',
        'min_qty',
        'expiry_date',
        'unit',
        'location',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'min_qty' => 'decimal:2',
            'expiry_date' => 'date',
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
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function shoppingListItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class, 'inventory_item_id');
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
     * Scope to get items with low stock.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('qty', '<', 'min_qty');
    }

    /**
     * Scope to get items expiring soon.
     */
    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        $date = Carbon::now()->addDays($days);
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $date)
            ->where('expiry_date', '>=', Carbon::now());
    }

    /**
     * Check if item is low on stock.
     */
    public function isLowStock(): bool
    {
        return $this->qty < $this->min_qty;
    }

    /**
     * Get days until expiry.
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if item needs restocking.
     */
    public function needsRestock(): bool
    {
        return $this->isLowStock();
    }
}
