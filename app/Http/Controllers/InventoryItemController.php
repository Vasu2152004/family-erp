<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class InventoryItemController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Display a listing of inventory items for a family.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('info', 'Please select a family to view inventory items.');
        }

        $this->authorize('viewAny', [InventoryItem::class, $family]);

        $query = InventoryItem::where('family_id', $family->id)
            ->with(['category', 'createdBy']);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('low_stock')) {
            $query->lowStock();
        }

        if ($request->filled('expiring_soon')) {
            $query->expiringSoon(7);
        }

        $items = $query->orderBy('name')
            ->paginate(20);

        $categories = InventoryCategory::where('family_id', $family->id)
            ->orderBy('name')
            ->get();

        return view('inventory.items.index', compact('family', 'items', 'categories'));
    }

    /**
     * Show the form for creating a new inventory item.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('info', 'Please select a family to create inventory items.');
        }

        $this->authorize('create', [InventoryItem::class, $family]);

        $categories = InventoryCategory::where('family_id', $family->id)
            ->orderBy('name')
            ->get();

        return view('inventory.items.create', compact('family', 'categories'));
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Please select a family to create inventory items.');
        }

        $this->authorize('create', [InventoryItem::class, $family]);

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0'],
            'min_qty' => ['required', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'unit' => ['required', 'in:piece,kg,liter,gram,ml,pack,box,bottle,other'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->inventoryService->createItem(
            array_merge($validated, ['created_by' => Auth::id()]),
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('inventory.items.index', ['family_id' => $family->id])
            ->with('success', 'Inventory item created successfully.');
    }

    /**
     * Show the form for editing the specified inventory item.
     */
    public function edit(Request $request, InventoryItem $item): View|RedirectResponse
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

        $categories = InventoryCategory::where('family_id', $family->id)
            ->orderBy('name')
            ->get();

        return view('inventory.items.edit', compact('family', 'item', 'categories'));
    }

    /**
     * Update the specified inventory item.
     */
    public function update(Request $request, InventoryItem $item): RedirectResponse
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
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0'],
            'min_qty' => ['required', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'unit' => ['required', 'in:piece,kg,liter,gram,ml,pack,box,bottle,other'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->inventoryService->updateItem($item->id, $validated);

        return redirect()->route('inventory.items.index', ['family_id' => $family->id])
            ->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Update item quantity (quick update).
     */
    public function updateQuantity(Request $request, InventoryItem $item): RedirectResponse
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
            'qty' => ['required', 'numeric', 'min:0'],
        ]);

        $this->inventoryService->updateQuantity($item->id, $validated['qty']);

        return redirect()->route('inventory.items.index', ['family_id' => $family->id])
            ->with('success', 'Item quantity updated successfully.');
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy(Request $request, InventoryItem $item): RedirectResponse
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

        $this->inventoryService->deleteItem($item->id);

        return redirect()->route('inventory.items.index', ['family_id' => $family->id])
            ->with('success', 'Inventory item deleted successfully.');
    }
}
