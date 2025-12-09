<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryItemBatch::class, 'inventory_item_id');
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
        return $query->whereRaw('(qty + COALESCE((SELECT SUM(qty) FROM inventory_item_batches WHERE inventory_item_batches.inventory_item_id = inventory_items.id), 0)) < min_qty');
    }

    /**
     * Scope to get items expiring soon.
     */
    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        $date = Carbon::now()->addDays($days);
        return $query->whereHas('batches', function ($q) use ($date) {
            $q->whereNotNull('expiry_date')
              ->where('expiry_date', '<=', $date)
              ->where('expiry_date', '>=', Carbon::now());
        });
    }

    /**
     * Check if item is low on stock.
     */
    public function isLowStock(): bool
    {
        return $this->getTotalQty() < $this->min_qty;
    }

    /**
     * Get total quantity including batches.
     */
    public function getTotalQty(): float
    {
        // If batches_total_qty was pre-calculated using withSum, use it
        if (isset($this->attributes['batches_total_qty'])) {
            return (float) $this->qty + (float) ($this->attributes['batches_total_qty'] ?? 0);
        }
        
        // If batches are loaded, use them; otherwise use a single efficient query
        if ($this->relationLoaded('batches')) {
            $batchesQty = (float) $this->batches->sum('qty');
        } else {
            // Use a single aggregate query instead of loading all batches
            $batchesQty = (float) $this->batches()->sum('qty');
        }

        return (float) $this->qty + $batchesQty;
    }

    /**
     * Get days until expiry.
     */
    public function daysUntilExpiry(): ?int
    {
        $earliestExpiry = $this->getEarliestExpiryDate();
        if (!$earliestExpiry) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($earliestExpiry, false);
    }

    /**
     * Get earliest expiry date from batches.
     */
    public function getEarliestExpiryDate(): ?Carbon
    {
        // If earliest_expiry_date was pre-calculated using withMin, use it
        if (isset($this->attributes['earliest_expiry_date'])) {
            return $this->attributes['earliest_expiry_date'] ? Carbon::parse($this->attributes['earliest_expiry_date']) : null;
        }
        
        if ($this->relationLoaded('batches')) {
            $earliest = $this->batches->whereNotNull('expiry_date')->min('expiry_date');
            return $earliest ? Carbon::parse($earliest) : null;
        }
        
        $earliest = $this->batches()->whereNotNull('expiry_date')->min('expiry_date');
        return $earliest ? Carbon::parse($earliest) : null;
    }

    /**
     * Check if item needs restocking.
     */
    public function needsRestock(): bool
    {
        return $this->isLowStock();
    }
}
