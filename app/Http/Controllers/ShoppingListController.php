<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\ShoppingListItem;
use App\Models\InventoryItem;
use App\Models\Budget;
use App\Models\FamilyMember;
use App\Services\ShoppingListService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ShoppingListController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private ShoppingListService $shoppingListService,
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Display the shopping list for a family.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('info', 'Please select a family to view shopping list.');
        }

        $this->authorize('viewAny', [ShoppingListItem::class, $family]);

        $pendingItems = ShoppingListItem::where('family_id', $family->id)
            ->pending()
            ->with([
                'inventoryItem:id,name,unit,family_id',
                'addedBy:id,name'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $purchasedItems = ShoppingListItem::where('family_id', $family->id)
            ->purchased()
            ->with([
                'inventoryItem:id,name,unit,family_id',
                'addedBy:id,name',
                'purchasedBy:id,name',
                'budget.category:id,name'
            ])
            ->orderBy('purchased_at', 'desc')
            ->paginate(10);

        // Only load inventory items that might be needed for the dropdown (limit to 100 most recent)
        $inventoryItems = InventoryItem::where('family_id', $family->id)
            ->select('id', 'name', 'unit', 'family_id')
            ->orderBy('name')
            ->limit(100)
            ->get();

        // Get budgets for current user (personal budgets + family budgets) - optimize with single query
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Cache user role and member for this request
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->select('role')
            ->first();
        $isAdminOrOwner = $userRole && in_array($userRole->role, ['OWNER', 'ADMIN']);

        $budgetsQuery = Budget::where('family_id', $family->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->with(['category:id,name']);

        if ($isAdminOrOwner) {
            // OWNER/ADMIN can see all budgets
            $budgets = $budgetsQuery->get();
        } else {
            // MEMBER can see family budgets + their own personal budgets
            $currentUserMember = FamilyMember::where('family_id', $family->id)
                ->where('user_id', Auth::id())
                ->select('id')
                ->first();
            $budgets = $budgetsQuery->where(function ($q) use ($currentUserMember) {
                $q->whereNull('family_member_id') // Family budgets
                    ->orWhere('family_member_id', $currentUserMember?->id); // Their personal budgets
            })->get();
        }

        return view('shopping-list.index', compact('family', 'pendingItems', 'purchasedItems', 'inventoryItems', 'budgets'));
    }

    /**
     * Store a newly created shopping list item.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Please select a family to add items to shopping list.');
        }

        $this->authorize('create', [ShoppingListItem::class, $family]);

        $validated = $request->validate([
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'name' => ['required_without:inventory_item_id', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'in:piece,kg,liter,gram,ml,pack,box,bottle,other'],
            'notes' => ['nullable', 'string'],
        ]);

        // If inventory_item_id is provided, get the name from inventory item
        if (isset($validated['inventory_item_id']) && empty($validated['name'])) {
            $inventoryItem = InventoryItem::find($validated['inventory_item_id']);
            if ($inventoryItem) {
                $validated['name'] = $inventoryItem->name;
            }
        }

        $this->shoppingListService->addItem(
            array_merge($validated, ['added_by' => Auth::id()]),
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('shopping-list.index', ['family_id' => $family->id])
            ->with('success', 'Item added to shopping list successfully.');
    }

    /**
     * Update the specified shopping list item.
     */
    public function update(Request $request, ShoppingListItem $item): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($item->family_id);
        }
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $item);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'in:piece,kg,liter,gram,ml,pack,box,bottle,other'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->shoppingListService->updateItem($item->id, $validated);

        return redirect()->route('shopping-list.index', ['family_id' => $family->id])
            ->with('success', 'Shopping list item updated successfully.');
    }

    /**
     * Mark item as purchased.
     */
    public function markPurchased(Request $request, ShoppingListItem $item): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($item->family_id);
        }
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Family not found.');
        }

        $this->authorize('markPurchased', $item);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
        ]);

        $amount = isset($validated['amount']) ? (float) $validated['amount'] : null;
        $budgetId = isset($validated['budget_id']) ? (int) $validated['budget_id'] : null;

        // If budget is provided, validate it belongs to the user
        if ($budgetId) {
            $budget = Budget::find($budgetId);
            if ($budget && $budget->family_id !== $family->id) {
                return redirect()->route('shopping-list.index', ['family_id' => $family->id])
                    ->with('error', 'Invalid budget selected.');
            }

            // If budget is personal, ensure it belongs to current user
            if ($budget && $budget->family_member_id) {
                $currentUserMember = FamilyMember::where('family_id', $family->id)
                    ->where('user_id', Auth::id())
                    ->first();
                
                if (!$currentUserMember || $budget->family_member_id !== $currentUserMember->id) {
                    return redirect()->route('shopping-list.index', ['family_id' => $family->id])
                        ->with('error', 'You can only use your own personal budgets.');
                }
            }
        }

        $this->shoppingListService->markAsPurchased($item->id, Auth::id(), $amount, $budgetId);

        $message = $amount 
            ? 'Item marked as purchased and transaction created successfully.'
            : 'Item marked as purchased.';

        return redirect()->route('shopping-list.index', ['family_id' => $family->id])
            ->with('success', $message);
    }

    /**
     * Mark item as pending.
     */
    public function markPending(Request $request, ShoppingListItem $item): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($item->family_id);
        }
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $item);

        $this->shoppingListService->markAsPending($item->id);

        return redirect()->route('shopping-list.index', ['family_id' => $family->id])
            ->with('success', 'Item marked as pending.');
    }

    /**
     * Auto-add low stock items to shopping list.
     */
    public function autoAddLowStock(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Please select a family.');
        }

        $this->authorize('create', [ShoppingListItem::class, $family]);

        // Get low stock items first to provide better feedback
        $lowStockItems = $this->inventoryService->checkLowStock($family->id);
        $lowStockCount = $lowStockItems->count();
        
        $addedItems = $this->shoppingListService->autoAddLowStockItems($family->id);
        $addedCount = $addedItems->count();

        if ($addedCount > 0) {
            return redirect()->route('shopping-list.index', ['family_id' => $family->id])
                ->with('success', $addedCount . ' low stock item' . ($addedCount > 1 ? 's' : '') . ' added to shopping list.');
        } elseif ($lowStockCount > 0) {
            return redirect()->route('shopping-list.index', ['family_id' => $family->id])
                ->with('info', $lowStockCount . ' low stock item' . ($lowStockCount > 1 ? 's were' : ' was') . ' found, but ' . ($lowStockCount > 1 ? 'they are' : 'it is') . ' already in your shopping list.');
        } else {
            return redirect()->route('shopping-list.index', ['family_id' => $family->id])
                ->with('info', 'No low stock items found. Make sure you have set a minimum quantity (min_qty > 0) for inventory items to enable low stock detection.');
        }
    }

    /**
     * Clear purchased items from shopping list.
     */
    public function clearPurchased(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Please select a family.');
        }

        $this->authorize('viewAny', [ShoppingListItem::class, $family]);

        $deletedCount = $this->shoppingListService->clearPurchasedItems($family->id);

        return redirect()->route('shopping-list.index', ['family_id' => $family->id])
            ->with('success', $deletedCount . ' purchased items cleared from shopping list.');
    }

    /**
     * Remove the specified shopping list item.
     */
    public function destroy(Request $request, ShoppingListItem $item): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($item->family_id);
        }
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Family not found.');
        }

        $this->authorize('delete', $item);

        $this->shoppingListService->deleteItem($item->id);

        return redirect()->route('shopping-list.index', ['family_id' => $family->id])
            ->with('success', 'Item removed from shopping list.');
    }
}
