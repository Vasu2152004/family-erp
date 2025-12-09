<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ShoppingListItem;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ShoppingListService
{
    public function __construct(
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Add item to shopping list.
     */
    public function addItem(array $data, int $tenantId, int $familyId): ShoppingListItem
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            return ShoppingListItem::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'inventory_item_id' => $data['inventory_item_id'] ?? null,
                'name' => $data['name'],
                'qty' => $data['qty'] ?? 1,
                'unit' => $data['unit'] ?? 'piece',
                'notes' => $data['notes'] ?? null,
                'added_by' => $data['added_by'] ?? auth()->id(),
                'is_auto_added' => $data['is_auto_added'] ?? false,
            ]);
        });
    }

    /**
     * Add item from inventory to shopping list.
     */
    public function addItemFromInventory(int $inventoryItemId, int $tenantId, int $familyId): ShoppingListItem
    {
        return DB::transaction(function () use ($inventoryItemId, $tenantId, $familyId) {
            $inventoryItem = InventoryItem::findOrFail($inventoryItemId);

            // Check if item already exists in shopping list and is not purchased
            $existingItem = ShoppingListItem::where('family_id', $familyId)
                ->where('inventory_item_id', $inventoryItemId)
                ->where('is_purchased', false)
                ->first();

            if ($existingItem) {
                // Update quantity if needed
                $existingItem->update([
                    'qty' => $existingItem->qty + ($inventoryItem->min_qty - $inventoryItem->qty),
                ]);
                return $existingItem->fresh();
            }

            // Calculate quantity needed (min_qty - current qty)
            $qtyNeeded = max(0, $inventoryItem->min_qty - $inventoryItem->qty);

            return ShoppingListItem::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'inventory_item_id' => $inventoryItemId,
                'name' => $inventoryItem->name,
                'qty' => $qtyNeeded > 0 ? $qtyNeeded : $inventoryItem->min_qty,
                'unit' => $inventoryItem->unit,
                'notes' => 'Auto-added from inventory',
                'added_by' => auth()->id(),
                'is_auto_added' => true,
            ]);
        });
    }

    /**
     * Update shopping list item.
     */
    public function updateItem(int $itemId, array $data): ShoppingListItem
    {
        return DB::transaction(function () use ($itemId, $data) {
            $item = ShoppingListItem::findOrFail($itemId);
            $item->update([
                'name' => $data['name'] ?? $item->name,
                'qty' => $data['qty'] ?? $item->qty,
                'unit' => $data['unit'] ?? $item->unit,
                'notes' => $data['notes'] ?? $item->notes,
            ]);

            return $item->fresh();
        });
    }

    /**
     * Delete shopping list item.
     */
    public function deleteItem(int $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $item = ShoppingListItem::findOrFail($itemId);
            $item->delete();
        });
    }

    /**
     * Mark item as purchased.
     */
    public function markAsPurchased(int $itemId, int $userId): ShoppingListItem
    {
        return DB::transaction(function () use ($itemId, $userId) {
            $item = ShoppingListItem::findOrFail($itemId);
            $item->markAsPurchased($userId);

            // If item is linked to inventory, update inventory quantity
            if ($item->inventory_item_id) {
                $inventoryItem = InventoryItem::find($item->inventory_item_id);
                if ($inventoryItem) {
                    $newQty = $inventoryItem->qty + $item->qty;
                    $this->inventoryService->updateQuantity($inventoryItem->id, $newQty, $userId);
                }
            }

            return $item->fresh();
        });
    }

    /**
     * Mark item as pending.
     */
    public function markAsPending(int $itemId): ShoppingListItem
    {
        return DB::transaction(function () use ($itemId) {
            $item = ShoppingListItem::findOrFail($itemId);
            $item->markAsPending();

            // If item was purchased and linked to inventory, reverse the quantity update
            if ($item->inventory_item_id && $item->purchased_at) {
                $inventoryItem = InventoryItem::find($item->inventory_item_id);
                if ($inventoryItem) {
                    $newQty = max(0, $inventoryItem->qty - $item->qty);
                    $this->inventoryService->updateQuantity($inventoryItem->id, $newQty);
                }
            }

            return $item->fresh();
        });
    }

    /**
     * Auto-add low stock items to shopping list.
     */
    public function autoAddLowStockItems(int $familyId): Collection
    {
        return DB::transaction(function () use ($familyId) {
            $lowStockItems = $this->inventoryService->checkLowStock($familyId);
            $family = \App\Models\Family::findOrFail($familyId);
            $addedItems = collect();

            foreach ($lowStockItems as $inventoryItem) {
                // Check if already in shopping list and not purchased
                $existing = ShoppingListItem::where('family_id', $familyId)
                    ->where('inventory_item_id', $inventoryItem->id)
                    ->where('is_purchased', false)
                    ->first();

                if (!$existing) {
                    $qtyNeeded = max(0, $inventoryItem->min_qty - $inventoryItem->qty);
                    if ($qtyNeeded > 0) {
                        $shoppingItem = ShoppingListItem::create([
                            'tenant_id' => $family->tenant_id,
                            'family_id' => $familyId,
                            'inventory_item_id' => $inventoryItem->id,
                            'name' => $inventoryItem->name,
                            'qty' => $qtyNeeded,
                            'unit' => $inventoryItem->unit,
                            'notes' => 'Auto-added: Low stock',
                            'added_by' => auth()->id(),
                            'is_auto_added' => true,
                        ]);
                        $addedItems->push($shoppingItem);
                    }
                }
            }

            return $addedItems;
        });
    }

    /**
     * Clear purchased items from shopping list.
     */
    public function clearPurchasedItems(int $familyId): int
    {
        return DB::transaction(function () use ($familyId) {
            return ShoppingListItem::where('family_id', $familyId)
                ->where('is_purchased', true)
                ->delete();
        });
    }

    /**
     * Get pending items.
     */
    public function getPendingItems(int $familyId): Collection
    {
        return ShoppingListItem::where('family_id', $familyId)
            ->pending()
            ->with(['inventoryItem', 'addedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

