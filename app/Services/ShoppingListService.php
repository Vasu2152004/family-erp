<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ShoppingListItem;
use App\Models\InventoryItem;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\FinanceAccount;
use App\Models\Budget;
use App\Models\FamilyMember;
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
    public function markAsPurchased(int $itemId, int $userId, ?float $amount = null, ?int $budgetId = null): ShoppingListItem
    {
        return DB::transaction(function () use ($itemId, $userId, $amount, $budgetId) {
            $item = ShoppingListItem::findOrFail($itemId);
            $item->markAsPurchased($userId);

            // Get family member of the user who marks as purchased
            $familyMember = FamilyMember::where('family_id', $item->family_id)
                ->where('user_id', $userId)
                ->first();

            // Create transaction if amount is provided
            $transaction = null;
            if ($amount && $amount > 0) {
                $transaction = $this->createPurchaseTransaction($item, $userId, $familyMember, $amount, $budgetId);
            }

            // Update item with purchase details
            $item->update([
                'amount' => $amount,
                'budget_id' => $budgetId,
                'transaction_id' => $transaction?->id,
            ]);

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
     * Create transaction for purchased item.
     */
    private function createPurchaseTransaction(ShoppingListItem $item, int $userId, ?FamilyMember $familyMember, float $amount, ?int $budgetId): Transaction
    {
        // Get or create shopping category
        $category = TransactionCategory::firstOrCreate(
            [
                'tenant_id' => $item->tenant_id,
                'family_id' => $item->family_id,
                'name' => 'Shopping',
                'type' => 'EXPENSE',
            ],
            [
                'is_system' => false,
                'icon' => '🛒',
                'color' => '#6366f1',
            ]
        );

        // Get default finance account
        $financeAccount = FinanceAccount::where('family_id', $item->family_id)
            ->where('is_active', true)
            ->first();

        if (!$financeAccount) {
            // Create a default cash account if none exists
            $financeAccount = FinanceAccount::create([
                'tenant_id' => $item->tenant_id,
                'family_id' => $item->family_id,
                'name' => 'Cash',
                'type' => 'CASH',
                'initial_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        // Validate budget if provided
        $budget = null;
        if ($budgetId) {
            $budget = Budget::where('id', $budgetId)
                ->where('family_id', $item->family_id)
                ->where('is_active', true)
                ->first();

            // If budget is personal, ensure it belongs to the family member
            if ($budget && $budget->family_member_id && $familyMember) {
                if ($budget->family_member_id !== $familyMember->id) {
                    $budget = null; // Budget doesn't belong to this member
                }
            }
        }

        // Create transaction
        $transaction = Transaction::create([
            'tenant_id' => $item->tenant_id,
            'family_id' => $item->family_id,
            'finance_account_id' => $financeAccount->id,
            'family_member_id' => $familyMember?->id, // Person who marks as purchased
            'category_id' => $category->id,
            'type' => 'EXPENSE',
            'amount' => $amount,
            'description' => "Shopping: {$item->name}",
            'transaction_date' => now()->toDateString(),
            'is_shared' => $familyMember ? false : true, // Shared if no member, private if has member
            'budget_id' => $budget?->id,
        ]);

        // Update account balance
        $financeAccount->updateBalance();

        return $transaction;
    }

    /**
     * Mark item as pending.
     */
    public function markAsPending(int $itemId): ShoppingListItem
    {
        return DB::transaction(function () use ($itemId) {
            $item = ShoppingListItem::findOrFail($itemId);
            
            // Delete transaction if exists
            if ($item->transaction_id) {
                $transaction = Transaction::find($item->transaction_id);
                if ($transaction) {
                    $accountId = $transaction->finance_account_id;
                    $transaction->delete();
                    
                    // Update account balance
                    if ($accountId) {
                        $account = FinanceAccount::find($accountId);
                        if ($account) {
                            $account->updateBalance();
                        }
                    }
                }
            }

            $item->markAsPending();
            
            // Clear purchase-related fields
            $item->update([
                'amount' => null,
                'budget_id' => null,
                'transaction_id' => null,
            ]);

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

