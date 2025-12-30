<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryItemBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class InventoryService
{
    /**
     * Create a new inventory category.
     */
    public function createCategory(array $data, int $tenantId, int $familyId): InventoryCategory
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            // IMPORTANT: Only check for duplicates within this specific family
            // Each family is completely isolated - same name can exist in different families
            $categoryName = trim($data['name']);
            
            // Double-check: ensure we're only checking within this family
            $existingCategory = InventoryCategory::where('family_id', $familyId)
                ->where('name', $categoryName)
                ->first();
            
            if ($existingCategory) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => ['A category with this name already exists for this family.'],
                ]);
            }
            
            try {
                return InventoryCategory::create([
                    'tenant_id' => $tenantId,
                    'family_id' => $familyId, // Explicitly set family_id to ensure isolation
                    'name' => $categoryName,
                    'description' => $data['description'] ?? null,
                    'icon' => $data['icon'] ?? null,
                    'color' => $data['color'] ?? null,
                    'created_by' => $data['created_by'] ?? auth()->id(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // If we still get a duplicate entry error, it means there was a race condition
                // Check again and throw validation exception
                if ($e->getCode() == 23000 && (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique'))) {
                    $exists = InventoryCategory::where('family_id', $familyId)
                        ->where('name', $categoryName)
                        ->exists();
                    
                    if ($exists) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'name' => ['A category with this name already exists for this family.'],
                        ]);
                    }
                }
                throw $e;
            }
        });
    }

    /**
     * Update an existing inventory category.
     */
    public function updateCategory(int $categoryId, array $data): InventoryCategory
    {
        return DB::transaction(function () use ($categoryId, $data) {
            $category = InventoryCategory::findOrFail($categoryId);
            
            // IMPORTANT: Only check for duplicates within this specific family
            // Each family is completely isolated - same name can exist in different families
            if (isset($data['name']) && trim($data['name']) !== $category->name) {
                $newName = trim($data['name']);
                $existingCategory = InventoryCategory::where('family_id', $category->family_id)
                    ->where('id', '!=', $categoryId)
                    ->where('name', $newName)
                    ->first();
                
                if ($existingCategory) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ['name' => ['A category with this name already exists for this family.']]
                    );
                }
            }
            
            $category->update([
                'name' => isset($data['name']) ? trim($data['name']) : $category->name,
                'description' => $data['description'] ?? $category->description,
                'icon' => $data['icon'] ?? $category->icon,
                'color' => $data['color'] ?? $category->color,
            ]);

            return $category->fresh();
        });
    }

    /**
     * Delete an inventory category.
     */
    public function deleteCategory(int $categoryId): void
    {
        DB::transaction(function () use ($categoryId) {
            $category = InventoryCategory::findOrFail($categoryId);
            $category->delete();
        });
    }

    /**
     * Create a new inventory item.
     */
    public function createItem(array $data, int $tenantId, int $familyId): InventoryItem
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            return InventoryItem::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'qty' => $data['qty'] ?? 0,
                'min_qty' => $data['min_qty'] ?? 0,
                'expiry_date' => $data['expiry_date'] ?? null,
                'unit' => $data['unit'] ?? 'piece',
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);
        });
    }

    /**
     * Update an existing inventory item.
     */
    public function updateItem(int $itemId, array $data): InventoryItem
    {
        return DB::transaction(function () use ($itemId, $data) {
            $item = InventoryItem::findOrFail($itemId);
            $item->update([
                'category_id' => $data['category_id'] ?? $item->category_id,
                'name' => $data['name'] ?? $item->name,
                'qty' => $data['qty'] ?? $item->qty, // qty is a base quantity; batches are additive
                'min_qty' => $data['min_qty'] ?? $item->min_qty,
                'expiry_date' => $data['expiry_date'] ?? $item->expiry_date,
                'unit' => $data['unit'] ?? $item->unit,
                'location' => $data['location'] ?? $item->location,
                'notes' => $data['notes'] ?? $item->notes,
                'updated_by' => auth()->id(),
            ]);

            return $item->fresh();
        });
    }

    /**
     * Delete an inventory item.
     */
    public function deleteItem(int $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $item = InventoryItem::findOrFail($itemId);
            // Delete batches first to maintain integrity
            $item->batches()->delete();
            $item->delete();
        });
    }

    /**
     * Add a new batch (lot) to an inventory item.
     */
    public function addBatch(array $data, int $tenantId, int $familyId): InventoryItemBatch
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            $item = InventoryItem::findOrFail($data['inventory_item_id']);

            $batch = InventoryItemBatch::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'inventory_item_id' => $item->id,
                'qty' => $data['qty'],
                'unit' => $data['unit'] ?? $item->unit ?? 'piece',
                'expiry_date' => $data['expiry_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'added_by' => $data['added_by'] ?? auth()->id(),
            ]);

            // Increment base qty to reflect total stock
            $item->increment('qty', $batch->qty);

            return $batch;
        });
    }

    /**
     * Check for low stock items in a family.
     */
    public function checkLowStock(int $familyId): Collection
    {
        return InventoryItem::where('family_id', $familyId)
            ->withSum('batches', 'qty')
            ->with(['category', 'createdBy'])
            ->get()
            ->filter(function (InventoryItem $item) {
                $total = (float) $item->qty + (float) ($item->batches_sum_qty ?? 0);
                return $total < (float) $item->min_qty;
            })
            ->values();
    }

    /**
     * Update item quantity.
     */
    public function updateQuantity(int $itemId, float $newQty, ?int $userId = null): InventoryItem
    {
        return DB::transaction(function () use ($itemId, $newQty, $userId) {
            $item = InventoryItem::findOrFail($itemId);
            $item->update([
                'qty' => $newQty,
                'updated_by' => $userId ?? auth()->id(),
            ]);

            return $item->fresh();
        });
    }

    /**
     * Log usage by subtracting quantity (consumes base qty first, then batches by earliest expiry).
     */
    public function logUsage(int $itemId, float $amount, ?int $userId = null): InventoryItem
    {
        return DB::transaction(function () use ($itemId, $amount, $userId) {
            $item = InventoryItem::with(['batches' => function ($q) {
                $q->orderBy('expiry_date')->orderBy('created_at');
            }])->lockForUpdate()->findOrFail($itemId);

            $totalQty = $item->getTotalQty();
            if ($amount > $totalQty) {
                throw new \InvalidArgumentException('Usage exceeds available quantity.');
            }

            $remaining = $amount;

            if ($item->qty >= $remaining) {
                $item->decrement('qty', $remaining);
                $remaining = 0;
            } else {
                $remaining -= $item->qty;
                $item->qty = 0;
                $item->save();
            }

            if ($remaining > 0) {
                foreach ($item->batches as $batch) {
                    if ($remaining <= 0) {
                        break;
                    }

                    if ($batch->qty >= $remaining) {
                        $batch->decrement('qty', $remaining);
                        $remaining = 0;
                    } else {
                        $remaining -= $batch->qty;
                        $batch->qty = 0;
                        $batch->save();
                    }

                    if ($batch->qty <= 0) {
                        $batch->delete();
                    }
                }
            }

            $item->update(['updated_by' => $userId ?? auth()->id()]);

            return $item->fresh();
        });
    }

    /**
     * Get items expiring soon.
     */
    public function getExpiringItems(int $familyId, int $days = 7): Collection
    {
        return InventoryItem::where('family_id', $familyId)
            ->expiringSoon($days)
            ->with(['category', 'createdBy'])
            ->get();
    }
}

