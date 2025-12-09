<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
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
            return InventoryCategory::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);
        });
    }

    /**
     * Update an existing inventory category.
     */
    public function updateCategory(int $categoryId, array $data): InventoryCategory
    {
        return DB::transaction(function () use ($categoryId, $data) {
            $category = InventoryCategory::findOrFail($categoryId);
            $category->update([
                'name' => $data['name'] ?? $category->name,
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
                'qty' => $data['qty'] ?? $item->qty,
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
            $item->delete();
        });
    }

    /**
     * Check for low stock items in a family.
     */
    public function checkLowStock(int $familyId): Collection
    {
        return InventoryItem::where('family_id', $familyId)
            ->lowStock()
            ->with(['category', 'createdBy'])
            ->get();
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

