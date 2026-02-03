<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\FinanceAccount;
use App\Models\TransactionCategory;
use App\Models\Budget;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TransactionController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private TransactionService $transactionService
    ) {
    }

    /**
     * Display a listing of transactions for a family.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('info', 'Please select a family to view transactions.');
        }

        $this->authorize('viewAny', [Transaction::class, $family]);

        $query = Transaction::where('family_id', $family->id);

        // Apply filters based on user role
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN')) {
            // OWNER/ADMIN can see all transactions
        } else {
            // MEMBER can only see own transactions + shared transactions
            $member = \App\Models\FamilyMember::where('family_id', $family->id)
                ->where('user_id', Auth::id())
                ->first();

            if ($member) {
                $query->where(function ($q) use ($member) {
                    $q->where('family_member_id', $member->id)
                        ->orWhere('is_shared', true);
                });
            } else {
                $query->where('is_shared', true);
            }
        }

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('family_member_id')) {
            $query->where('family_member_id', $request->family_member_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        $transactions = $query->with(['financeAccount', 'transferToAccount', 'familyMember', 'category', 'budget.category', 'budget.familyMember.user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->simplePaginate(10);

        // Get filter options (request-level cached)
        $accounts = $this->getCachedFinanceAccounts($family->id);
        $categories = $this->getCachedTransactionCategories($family->id);
        $members = $this->getCachedFamilyMembers($family);

        $user = once(fn () => Auth::user());
        $canUpdateIds = $transactions->getCollection()->filter(fn (Transaction $t) => Gate::forUser($user)->allows('update', $t))->pluck('id')->all();
        $canDeleteIds = $transactions->getCollection()->filter(fn (Transaction $t) => Gate::forUser($user)->allows('delete', $t))->pluck('id')->all();

        return view('transactions.index', compact('family', 'transactions', 'accounts', 'categories', 'members', 'canUpdateIds', 'canDeleteIds'));
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('info', 'Please select a family to create transactions.');
        }

        $this->authorize('create', [Transaction::class, $family]);

        $accounts = $this->getCachedActiveFinanceAccounts($family->id);
        $categories = $this->getCachedTransactionCategories($family->id);
        if ($categories->isEmpty()) {
            $seeder = new \Database\Seeders\TransactionCategorySeeder();
            $seeder->seedForFamily($family->tenant_id, $family->id);
            app()->forgetInstance('transaction_categories_' . $family->id);
            $categories = $this->getCachedTransactionCategories($family->id);
        }
        $members = $this->getCachedFamilyMembers($family);
        
        // Get active budgets for the current month/year
        // Filter: Show family budgets + only personal budgets that belong to current user
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Check user role
        $user = once(fn () => Auth::user());
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();
        $isAdminOrOwner = $userRole && in_array($userRole->role, ['OWNER', 'ADMIN']);
        $isMember = $userRole && $userRole->role === 'MEMBER';

        // Get current user's family member record
        $currentUserMember = \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();
        
        $budgetsQuery = Budget::where('family_id', $family->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('is_active', true);
        
        if ($isAdminOrOwner) {
            // OWNER/ADMIN can see all budgets
            $budgets = $budgetsQuery->with(['category', 'familyMember.user'])->get();
        } else {
            // Regular members can only see:
            // 1. Family budgets (no family_member_id)
            // 2. Their own personal budgets
            $budgetsQuery->where(function ($query) use ($currentUserMember) {
                $query->whereNull('family_member_id') // Family budgets
                    ->orWhere('family_member_id', $currentUserMember?->id); // Own personal budgets
            });
            $budgets = $budgetsQuery->with(['category', 'familyMember.user'])->get();
        }

        return view('transactions.create', compact('family', 'accounts', 'categories', 'members', 'budgets', 'isMember'));
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Please select a family to create transactions.');
        }

        $this->authorize('create', [Transaction::class, $family]);

        $validated = $request->validate([
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'category_id' => ['nullable', 'exists:transaction_categories,id'],
            'type' => ['required', 'in:INCOME,EXPENSE,TRANSFER'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
            'is_shared' => ['nullable', 'boolean'],
            'transfer_to_account_id' => ['required_if:type,TRANSFER', 'nullable', 'exists:finance_accounts,id'],
            'budget_allocation' => ['nullable', 'in:personal,family,both'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
        ]);
        
        // Convert checkbox value to boolean (checkbox sends "1" when checked, nothing when unchecked)
        $validated['is_shared'] = (bool) ($request->has('is_shared') && $request->input('is_shared') == '1');

        $this->transactionService->createTransaction(
            $validated,
            $family->tenant_id,
            $family->id
        );

        return redirect()->route('finance.transactions.index', ['family_id' => $family->id])
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Request $request, Transaction $transaction): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($transaction->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $transaction);

        $accounts = $this->getCachedActiveFinanceAccounts($family->id);
        $categories = $this->getCachedTransactionCategories($family->id);
        $members = $this->getCachedFamilyMembers($family);
        
        // Get active budgets for the transaction's month/year
        // Filter: Show family budgets + only personal budgets that belong to current user
        $transactionDate = \Carbon\Carbon::parse($transaction->transaction_date);
        
        // Check user role
        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->first();
        $isAdminOrOwner = $userRole && in_array($userRole->role, ['OWNER', 'ADMIN']);
        
        // Get current user's family member record
        $currentUserMember = \App\Models\FamilyMember::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->first();
        
        $budgetsQuery = Budget::where('family_id', $family->id)
            ->where('month', $transactionDate->month)
            ->where('year', $transactionDate->year)
            ->where('is_active', true);
        
        if ($isAdminOrOwner) {
            // OWNER/ADMIN can see all budgets
            $budgets = $budgetsQuery->with(['category', 'familyMember.user'])->get();
        } else {
            // Regular members can only see:
            // 1. Family budgets (no family_member_id)
            // 2. Their own personal budgets
            $budgetsQuery->where(function ($query) use ($currentUserMember) {
                $query->whereNull('family_member_id') // Family budgets
                    ->orWhere('family_member_id', $currentUserMember?->id); // Own personal budgets
            });
            $budgets = $budgetsQuery->with(['category', 'familyMember.user'])->get();
        }

        return view('transactions.edit', compact('family', 'transaction', 'accounts', 'categories', 'members', 'budgets'));
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($transaction->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('update', $transaction);

        $validated = $request->validate([
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'category_id' => ['nullable', 'exists:transaction_categories,id'],
            'type' => ['required', 'in:INCOME,EXPENSE'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
            'is_shared' => ['nullable', 'boolean'],
            'budget_allocation' => ['nullable', 'in:personal,family,both'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
        ]);
        
        // Convert checkbox value to boolean (checkbox sends "1" when checked, nothing when unchecked)
        $validated['is_shared'] = (bool) ($request->has('is_shared') && $request->input('is_shared') == '1');

        $this->transactionService->updateTransaction($transaction->id, $validated);

        return redirect()->route('finance.transactions.index', ['family_id' => $family->id])
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            $family = Family::find($transaction->family_id);
        }
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('error', 'Family not found.');
        }

        $this->authorize('delete', $transaction);

        $this->transactionService->deleteTransaction($transaction->id);

        return redirect()->route('finance.transactions.index', ['family_id' => $family->id])
            ->with('success', 'Transaction deleted successfully.');
    }

    private function getCachedFinanceAccounts(int $familyId)
    {
        $key = 'finance_accounts_' . $familyId;
        if (!app()->bound($key)) {
            app()->instance($key, FinanceAccount::where('family_id', $familyId)->get());
        }
        return app($key);
    }

    private function getCachedActiveFinanceAccounts(int $familyId)
    {
        $key = 'finance_accounts_active_' . $familyId;
        if (!app()->bound($key)) {
            app()->instance($key, FinanceAccount::where('family_id', $familyId)->where('is_active', true)->get());
        }
        return app($key);
    }

    private function getCachedTransactionCategories(int $familyId)
    {
        $key = 'transaction_categories_' . $familyId;
        if (!app()->bound($key)) {
            app()->instance($key, TransactionCategory::where('family_id', $familyId)->get());
        }
        return app($key);
    }

    private function getCachedFamilyMembers(Family $family)
    {
        $key = 'family_members_' . $family->id;
        if (!app()->bound($key)) {
            $service = app(\App\Services\FamilyMemberService::class);
            app()->instance($key, $service->getMembersForSelection($family));
        }
        return app($key);
    }
}
