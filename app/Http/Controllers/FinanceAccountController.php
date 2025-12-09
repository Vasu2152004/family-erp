<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\FinanceAccount;
use App\Services\FinanceAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceAccountController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private FinanceAccountService $financeAccountService
    ) {
    }

    /**
     * Display a listing of finance accounts for a family.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('info', 'Please select a family to view finance accounts.');
        }

        $this->authorize('viewAny', [FinanceAccount::class, $family]);

        $accounts = FinanceAccount::where('family_id', $family->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('finance-accounts.index', compact('family', 'accounts'));
    }

    /**
     * Show the form for creating a new finance account.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('info', 'Please select a family to create finance accounts.');
        }

        $this->authorize('create', [FinanceAccount::class, $family]);

        return view('finance-accounts.create', compact('family'));
    }

    /**
     * Store a newly created finance account.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Please select a family to create finance accounts.');
        }

        $this->authorize('create', [FinanceAccount::class, $family]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $this->financeAccountService->createAccount(
            $validated,
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('finance.accounts.index', ['family_id' => $family->id])
            ->with('success', 'Finance account created successfully.');
    }
    }

    /**
     * Show the form for editing the specified finance account.
     */
    public function edit(Request $request, FinanceAccount $financeAccount): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($financeAccount->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $financeAccount);

        return view('finance-accounts.edit', compact('family', 'financeAccount'));
    }

    /**
     * Update the specified finance account.
     */
    public function update(Request $request, FinanceAccount $financeAccount): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($financeAccount->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $financeAccount);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $this->financeAccountService->updateAccount($financeAccount->id, $validated);

        return redirect()->route('finance.accounts.index', ['family_id' => $family->id])
            ->with('success', 'Finance account updated successfully.');
    }

    /**
     * Remove the specified finance account.
     */
    public function destroy(Request $request, FinanceAccount $financeAccount): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($financeAccount->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('delete', $financeAccount);

        try {
            $this->financeAccountService->deleteAccount($financeAccount->id);

            return redirect()->route('finance.accounts.index', ['family_id' => $family->id])
                ->with('success', 'Finance account deleted successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('finance.accounts.index', ['family_id' => $family->id])
                ->withErrors($e->errors());
        }
    }
}
