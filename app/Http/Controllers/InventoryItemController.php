<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Services\InventoryService;
use App\Services\InventoryAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class InventoryItemController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private InventoryService $inventoryService,
        private InventoryAnalyticsService $analyticsService
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
            ->with([
                'category:id,name,color,icon',
                'createdBy:id,name',
                'batches' => function ($q) {
                    $q->select('id', 'inventory_item_id', 'qty', 'unit', 'expiry_date', 'notes')
                      ->orderBy('expiry_date')
                      ->orderBy('created_at');
                }
            ])
            ->withSum('batches as batches_total_qty', 'qty')
            ->withMin('batches as earliest_expiry_date', 'expiry_date');

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
            ->paginate(10);

        $categories = InventoryCategory::where('family_id', $family->id)
            ->orderBy('name')
            ->get();

        // Get analytics data for charts
        $categoryDistribution = $this->analyticsService->getCategoryWiseDistribution($family->id);
        $stockStatusOverview = $this->analyticsService->getStockStatusOverview($family->id);

        return view('inventory.items.index', compact(
            'family',
            'items',
            'categories',
            'categoryDistribution',
            'stockStatusOverview'
        ));
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

        return redirect()->route('families.inventory.items.index', ['family' => $family->id])
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

        $item->load(['batches' => function ($query) {
            $query->orderBy('expiry_date')->orderBy('created_at');
        }, 'batches.addedBy']);

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

        return redirect()->route('families.inventory.items.index', ['family' => $family->id])
            ->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Add a new batch/lot to an inventory item.
     */
    public function storeBatch(Request $request, InventoryItem $item): RedirectResponse
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
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'in:piece,kg,liter,gram,ml,pack,box,bottle,other'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->inventoryService->addBatch(
            array_merge($validated, [
                'inventory_item_id' => $item->id,
                'added_by' => Auth::id(),
            ]),
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('families.inventory.items.edit', ['family' => $family->id, 'item' => $item->id])
            ->with('success', 'Batch added successfully.');
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

        return redirect()->route('families.inventory.items.index', ['family' => $family->id])
            ->with('success', 'Item quantity updated successfully.');
    }

    /**
     * Log usage by subtracting quantity.
     */
    public function logUsage(Request $request, Family $family, InventoryItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'amount_used' => ['required', 'numeric', 'min:0.01'],
        ]);

        $amount = (float) $validated['amount_used'];
        $available = $item->getTotalQty();

        if ($amount > $available) {
            return redirect()->route('families.inventory.items.index', ['family' => $family->id])
                ->with('error', 'Usage exceeds available quantity.');
        }

        try {
            $this->inventoryService->logUsage($item->id, $amount, $request->user()?->id);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('families.inventory.items.index', ['family' => $family->id])
                ->with('error', $e->getMessage());
        }

        return redirect()->route('families.inventory.items.index', ['family' => $family->id])
            ->with('success', 'Usage logged and quantity updated.');
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

        return redirect()->route('families.inventory.items.index', ['family' => $family->id])
            ->with('success', 'Inventory item deleted successfully.');
    }
}
