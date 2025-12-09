<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\InventoryCategory;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class InventoryCategoryController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Display a listing of inventory categories for a family.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('info', 'Please select a family to view inventory categories.');
        }

        $this->authorize('viewAny', [InventoryCategory::class, $family]);

        $categories = InventoryCategory::where('family_id', $family->id)
            ->with('createdBy')
            ->orderBy('name')
            ->get();

        return view('inventory.categories.index', compact('family', 'categories'));
    }

    /**
     * Show the form for creating a new inventory category.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('info', 'Please select a family to create inventory categories.');
        }

        $this->authorize('create', [InventoryCategory::class, $family]);

        return view('inventory.categories.create', compact('family'));
    }

    /**
     * Store a newly created inventory category.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Please select a family to create inventory categories.');
        }

        $this->authorize('create', [InventoryCategory::class, $family]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:inventory_categories,name,NULL,id,family_id,' . $family->id],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $this->inventoryService->createCategory(
            array_merge($validated, ['created_by' => Auth::id()]),
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('inventory.categories.index', ['family_id' => $family->id])
            ->with('success', 'Inventory category created successfully.');
    }

    /**
     * Show the form for editing the specified inventory category.
     */
    public function edit(Request $request, InventoryCategory $category): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($category->family_id);
        }
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $category);

        return view('inventory.categories.edit', compact('family', 'category'));
    }

    /**
     * Update the specified inventory category.
     */
    public function update(Request $request, InventoryCategory $category): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($category->family_id);
        }
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:inventory_categories,name,' . $category->id . ',id,family_id,' . $family->id],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $this->inventoryService->updateCategory($category->id, $validated);

        return redirect()->route('inventory.categories.index', ['family_id' => $family->id])
            ->with('success', 'Inventory category updated successfully.');
    }

    /**
     * Remove the specified inventory category.
     */
    public function destroy(Request $request, InventoryCategory $category): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($category->family_id);
        }
        
        if (!$family) {
            return redirect()->route('dashboard')
                ->with('error', 'Family not found.');
        }

        $this->authorize('delete', $category);

        $this->inventoryService->deleteCategory($category->id);

        return redirect()->route('inventory.categories.index', ['family_id' => $family->id])
            ->with('success', 'Inventory category deleted successfully.');
    }
}
