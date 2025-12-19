<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\Budget;
use App\Models\TransactionCategory;
use App\Services\BudgetService;
use App\Services\BudgetAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private BudgetService $budgetService,
        private BudgetAnalyticsService $analyticsService
    ) {
    }

    /**
     * Display a listing of budgets for a family.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('info', 'Please select a family to view budgets.');
        }

        $this->authorize('viewAny', [Budget::class, $family]);

        $currentMonth = now()->month;
        $currentYear = now()->year;

        $budgets = Budget::where('family_id', $family->id)
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->with(['category', 'familyMember.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get budget status for each (map through paginated collection)
        $budgetsWithStatus = $budgets->getCollection()->map(function ($budget) {
            $status = $this->budgetService->getBudgetStatus($budget->id);
            return [
                'budget' => $budget,
                'status' => $status,
            ];
        });

        // Set the mapped collection back to the paginator
        $budgets->setCollection($budgetsWithStatus);

        $categories = TransactionCategory::where('family_id', $family->id)
            ->where('type', 'EXPENSE')
            ->get();

        // Get analytics data for charts
        $budgetVsActualData = $this->analyticsService->getBudgetVsActual($family->id, $currentMonth, $currentYear);

        return view('budgets.index', compact('family', 'budgets', 'budgetsWithStatus', 'categories', 'currentMonth', 'currentYear', 'budgetVsActualData'));
    }

    /**
     * Show the form for creating a new budget.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('info', 'Please select a family to create budgets.');
        }

        $this->authorize('create', [Budget::class, $family]);

        $categories = TransactionCategory::where('family_id', $family->id)
            ->where('type', 'EXPENSE')
            ->get();

        $members = $family->members()->with('user')->get();

        return view('budgets.create', compact('family', 'categories', 'members'));
    }

    /**
     * Store a newly created budget.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Please select a family to create budgets.');
        }

        $this->authorize('create', [Budget::class, $family]);

        $validated = $request->validate([
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'category_id' => ['nullable', 'exists:transaction_categories,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $this->budgetService->createOrUpdateBudget(
            $validated,
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('finance.budgets.index', ['family_id' => $family->id])
            ->with('success', 'Budget created successfully.');
    }

    /**
     * Show the form for editing the specified budget.
     */
    public function edit(Request $request, Budget $budget): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($budget->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $budget);

        $categories = TransactionCategory::where('family_id', $family->id)
            ->where('type', 'EXPENSE')
            ->get();

        $members = $family->members()->with('user')->get();

        return view('budgets.edit', compact('family', 'budget', 'categories', 'members'));
    }

    /**
     * Update the specified budget.
     */
    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($budget->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $budget);

        $validated = $request->validate([
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'category_id' => ['nullable', 'exists:transaction_categories,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $this->budgetService->createOrUpdateBudget(
            $validated,
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('finance.budgets.index', ['family_id' => $family->id])
            ->with('success', 'Budget updated successfully.');
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($budget->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('delete', $budget);

        $budget->delete();

        return redirect()->route('finance.budgets.index', ['family_id' => $family->id])
            ->with('success', 'Budget deleted successfully.');
    }
}
