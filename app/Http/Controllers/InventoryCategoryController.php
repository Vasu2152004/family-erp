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
            ->paginate(10);

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

        // Ensure we're only checking within this specific family
        $familyId = $family->id;
        
        // Pre-validate: check for duplicate name in this family before Laravel validation
        $categoryName = trim($request->input('name', ''));
        if ($categoryName) {
            $duplicateExists = \App\Models\InventoryCategory::where('family_id', $familyId)
                ->where('name', $categoryName)
                ->exists();
            
            if ($duplicateExists) {
                return redirect()->back()
                    ->withErrors(['name' => 'A category with this name already exists for this family.'])
                    ->withInput();
            }
        }
        
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($familyId) {
                    // Only check for duplicates within this specific family
                    $trimmedName = trim($value);
                    $exists = \App\Models\InventoryCategory::where('family_id', $familyId)
                        ->where('name', $trimmedName)
                        ->exists();
                    if ($exists) {
                        $fail('A category with this name already exists for this family.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        try {
            $this->inventoryService->createCategory(
                array_merge($validated, ['created_by' => Auth::id()]),
                $family->tenant_id,
                $family->id
            );

            return redirect()->route('inventory.categories.index', ['family_id' => $family->id])
                ->with('success', 'Inventory category created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exception from service layer
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation from database
            if ($e->getCode() == 23000 && (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique'))) {
                return redirect()->back()
                    ->withErrors(['name' => 'A category with this name already exists for this family.'])
                    ->withInput();
            }
            throw $e;
        } catch (\Exception $e) {
            // Catch any other exceptions
            \Log::error('Error creating inventory category: ' . $e->getMessage(), [
                'family_id' => $family->id,
                'name' => $validated['name'] ?? null,
                'exception' => $e
            ]);
            
            return redirect()->back()
                ->withErrors(['name' => 'An error occurred while creating the category. Please try again.'])
                ->withInput();
        }
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

        // Ensure we're only checking within this specific family
        $familyId = $family->id;
        $categoryId = $category->id;
        
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($familyId, $categoryId) {
                    // Only check for duplicates within this specific family, excluding current category
                    $exists = \App\Models\InventoryCategory::where('family_id', $familyId)
                        ->where('id', '!=', $categoryId)
                        ->where('name', trim($value))
                        ->exists();
                    if ($exists) {
                        $fail('A category with this name already exists for this family.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        try {
            $this->inventoryService->updateCategory($category->id, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exception from service layer
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation from database
            if ($e->getCode() == 23000 && (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique'))) {
                return redirect()->back()
                    ->withErrors(['name' => 'A category with this name already exists for this family.'])
                    ->withInput();
            }
            throw $e;
        } catch (\Exception $e) {
            // Catch any other exceptions
            \Log::error('Error updating inventory category: ' . $e->getMessage(), [
                'category_id' => $category->id,
                'family_id' => $family->id,
                'name' => $validated['name'] ?? null,
                'exception' => $e
            ]);
            
            return redirect()->back()
                ->withErrors(['name' => 'An error occurred while updating the category. Please try again.'])
                ->withInput();
        }

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
