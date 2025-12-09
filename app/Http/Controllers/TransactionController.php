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

        $transactions = $query->with(['financeAccount', 'familyMember', 'category', 'budget.category', 'budget.familyMember.user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get filter options
        $accounts = FinanceAccount::where('family_id', $family->id)->get();
        $categories = TransactionCategory::where('family_id', $family->id)->get();
        $members = $family->members()->with('user')->get();

        return view('transactions.index', compact('family', 'transactions', 'accounts', 'categories', 'members'));
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

        $accounts = FinanceAccount::where('family_id', $family->id)
            ->where('is_active', true)
            ->get();
        $categories = TransactionCategory::where('family_id', $family->id)->get();
        
        // Auto-create categories if none exist
        if ($categories->isEmpty()) {
            $seeder = new \Database\Seeders\TransactionCategorySeeder();
            $seeder->seedForFamily($family->tenant_id, $family->id);
            $categories = TransactionCategory::where('family_id', $family->id)->get();
        }
        
        $members = $family->members()->with('user')->get();
        
        // Get active budgets for the current month/year
        // Filter: Show family budgets + only personal budgets that belong to current user
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
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

        return view('transactions.create', compact('family', 'accounts', 'categories', 'members', 'budgets'));
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
            'is_shared' => ['boolean'],
            'transfer_to_account_id' => ['required_if:type,TRANSFER', 'nullable', 'exists:finance_accounts,id'],
            'budget_allocation' => ['required_if:type,EXPENSE', 'nullable', 'in:personal,family,both'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
        ]);

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

        $accounts = FinanceAccount::where('family_id', $family->id)
            ->where('is_active', true)
            ->get();
        $categories = TransactionCategory::where('family_id', $family->id)->get();
        $members = $family->members()->with('user')->get();
        
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
            'is_shared' => ['boolean'],
            'budget_allocation' => ['required_if:type,EXPENSE', 'nullable', 'in:personal,family,both'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
        ]);

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
}
