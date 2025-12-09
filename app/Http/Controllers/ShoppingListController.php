<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\ShoppingListItem;
use App\Models\InventoryItem;
use App\Services\ShoppingListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ShoppingListController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private ShoppingListService $shoppingListService
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
            ->with(['inventoryItem', 'addedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $purchasedItems = ShoppingListItem::where('family_id', $family->id)
            ->purchased()
            ->with(['inventoryItem', 'addedBy', 'purchasedBy'])
            ->orderBy('purchased_at', 'desc')
            ->limit(20)
            ->get();

        $inventoryItems = InventoryItem::where('family_id', $family->id)
            ->orderBy('name')
            ->get();

        return view('shopping-list.index', compact('family', 'pendingItems', 'purchasedItems', 'inventoryItems'));
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
            'name' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'in:piece,kg,liter,gram,ml,pack,box,bottle,other'],
            'notes' => ['nullable', 'string'],
        ]);

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

        $this->shoppingListService->markAsPurchased($item->id, Auth::id());

        return redirect()->route('shopping-list.index', ['family_id' => $family->id])
            ->with('success', 'Item marked as purchased.');
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

        $addedItems = $this->shoppingListService->autoAddLowStockItems($family->id);

        return redirect()->route('shopping-list.index', ['family_id' => $family->id])
            ->with('success', $addedItems->count() . ' low stock items added to shopping list.');
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
