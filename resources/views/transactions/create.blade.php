<x-app-layout title="Add Transaction: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Transactions', 'url' => route('finance.transactions.index', ['family_id' => $family->id])],
            ['label' => 'Add Transaction']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Add Transaction</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Record a new transaction for {{ $family->name }}
                </p>
            </div>

        <x-form method="POST" action="{{ route('finance.transactions.store', ['family_id' => $family->id]) }}" id="transactionForm" class="space-y-6">
            <input type="hidden" name="family_id" value="{{ $family->id }}">

            @php
                $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
                    ->where('user_id', Auth::id())
                    ->first();
                $isMember = $userRole && $userRole->role === 'MEMBER';
                $preSelectedType = request()->input('type') ?? old('type');
            @endphp
            <div>
                <x-label for="type" required>Transaction Type</x-label>
                @if($preSelectedType && in_array($preSelectedType, ['INCOME', 'EXPENSE']))
                    <input type="hidden" name="type" value="{{ $preSelectedType }}">
                    <div class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)]">
                        {{ $preSelectedType === 'INCOME' ? 'Income' : 'Expense' }}
                    </div>
                @else
                    <select name="type" id="type" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">Select type...</option>
                        <option value="INCOME" {{ old('type') == 'INCOME' ? 'selected' : '' }}>Income</option>
                        <option value="EXPENSE" {{ old('type') == 'EXPENSE' ? 'selected' : '' }}>Expense</option>
                        @if(!$isMember)
                            <option value="TRANSFER" {{ old('type') == 'TRANSFER' ? 'selected' : '' }}>Transfer</option>
                        @endif
                    </select>
                @endif
                <p class="mt-1 text-xs text-[var(--color-text-secondary)]" id="budget_hint" style="display: none;">
                    💡 <strong>Tip:</strong> Select "Expense" to assign this transaction to a specific budget.
                </p>
                <x-error-message field="type" />
            </div>

            <div>
                <x-label for="finance_account_id" required>Account</x-label>
                <select name="finance_account_id" id="finance_account_id" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="">Select account...</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('finance_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }} ({{ $account->type }})</option>
                    @endforeach
                </select>
                <x-error-message field="finance_account_id" />
            </div>

            @if(!$isMember)
                <div id="transfer_to_account_field" style="display: none;">
                    <x-label for="transfer_to_account_id">Transfer To Account</x-label>
                    <select name="transfer_to_account_id" id="transfer_to_account_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">Select account...</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('transfer_to_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }} ({{ $account->type }})</option>
                        @endforeach
                    </select>
                    <x-error-message field="transfer_to_account_id" />
                </div>
            @endif

            <div>
                <x-label for="amount" required>Amount</x-label>
                <x-input 
                    type="number" 
                    name="amount" 
                    id="amount" 
                    value="{{ old('amount') }}" 
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    required
                    class="mt-1"
                />
                <x-error-message field="amount" />
            </div>

            <div>
                <x-label for="category_id">Category</x-label>
                <select name="category_id" id="category_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="">Select category...</option>
                    @if(isset($categories) && $categories && $categories->count() > 0)
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }} ({{ $category->type }})</option>
                        @endforeach
                    @else
                        <option value="" disabled>No categories available. Please create categories first.</option>
                    @endif
                </select>
                @if(!isset($categories) || !$categories || $categories->count() === 0)
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">No categories found. Categories will be created automatically or you can create them in settings.</p>
                @endif
                <x-error-message field="category_id" />
            </div>

            <div id="budget_field">
                <x-label for="budget_id">
                    Assign to Specific Budget 
                    <span class="text-xs text-[var(--color-text-secondary)]">(Only for Expenses)</span>
                </x-label>
                <select name="budget_id" id="budget_id" {{ ($preSelectedType !== 'EXPENSE') ? 'disabled' : '' }} class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" style="{{ ($preSelectedType === 'EXPENSE') ? 'background-color: var(--color-bg-primary); color: var(--color-text-primary); cursor: pointer; opacity: 1; pointer-events: auto;' : 'background-color: var(--color-bg-secondary); color: var(--color-text-secondary); cursor: not-allowed; opacity: 0.6; pointer-events: none;' }}">
                    <option value="">{{ ($preSelectedType === 'EXPENSE') ? 'No specific budget (will not affect any budget)' : 'Please select "Expense" as transaction type first' }}</option>
                    @if(isset($budgets) && $budgets && $budgets->count() > 0)
                        @foreach($budgets as $budget)
                            <option value="{{ $budget->id }}" {{ old('budget_id') == $budget->id ? 'selected' : '' }}>
                                @if($budget->isPersonal())
                                    {{ $budget->familyMember?->user?->name ?? 'Personal' }} - 
                                @else
                                    Family - 
                                @endif
                                {{ $budget->category?->name ?? 'Total' }} - 
                                {{ \Carbon\Carbon::create($budget->year, $budget->month, 1)->format('M Y') }} 
                                ({{ number_format($budget->amount, 2) }})
                            </option>
                        @endforeach
                    @else
                        <option value="" disabled>No active budgets found for this month</option>
                    @endif
                </select>
                <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Select a specific budget this expense will be deducted from. Enable this field by selecting "Expense" as the transaction type.</p>
                <x-error-message field="budget_id" />
            </div>

            <div>
                <x-label for="family_member_id">Family Member</x-label>
                <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="">Select member...</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" {{ old('family_member_id') == $member->id ? 'selected' : '' }}>{{ $member->first_name }} {{ $member->last_name }}</option>
                    @endforeach
                </select>
                <x-error-message field="family_member_id" />
            </div>

            <div>
                <x-label for="transaction_date" required>Transaction Date</x-label>
                <x-input 
                    type="date" 
                    name="transaction_date" 
                    id="transaction_date" 
                    value="{{ old('transaction_date', now()->format('Y-m-d')) }}" 
                    required
                    class="mt-1"
                />
                <x-error-message field="transaction_date" />
            </div>

            <div>
                <x-label for="description">Description</x-label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="3"
                    placeholder="Optional description"
                    class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
                >{{ old('description') }}</textarea>
                <x-error-message field="description" />
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_shared" id="is_shared" value="1" {{ old('is_shared') ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                <x-label for="is_shared" class="ml-2">Share with other members</x-label>
            </div>

            <div class="flex gap-4">
                <x-button type="submit" variant="primary" size="md">
                    Create Transaction
                </x-button>
                <a href="{{ route('finance.transactions.index', ['family_id' => $family->id]) }}">
                    <x-button type="button" variant="outline" size="md">
                        Cancel
                    </x-button>
                </a>
            </div>
        </x-form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Direct inline script - no dependencies
            console.log('Script section reached');
            
            function initBudgetField() {
                console.log('Initializing budget field');
                const typeSelect = document.getElementById('type');
                const budgetSelect = document.getElementById('budget_id');
                
                console.log('Type select found:', !!typeSelect);
                console.log('Budget select found:', !!budgetSelect);
                
                if (!typeSelect || !budgetSelect) {
                    console.error('Required elements not found');
                    return;
                }
                
                // Function to enable/disable budget field
                function updateBudgetField() {
                    const isExpense = typeSelect.value === 'EXPENSE';
                    console.log('Updating budget field. Current type:', typeSelect.value, 'Is expense:', isExpense);
                    
                    if (isExpense) {
                        // Enable
                        budgetSelect.removeAttribute('disabled');
                        budgetSelect.disabled = false;
                        budgetSelect.style.backgroundColor = 'var(--color-bg-primary)';
                        budgetSelect.style.color = 'var(--color-text-primary)';
                        budgetSelect.style.cursor = 'pointer';
                        budgetSelect.style.opacity = '1';
                        budgetSelect.style.pointerEvents = 'auto';
                        console.log('✅ Budget field ENABLED. Disabled status:', budgetSelect.disabled);
                    } else {
                        // Disable
                        budgetSelect.disabled = true;
                        budgetSelect.setAttribute('disabled', 'disabled');
                        budgetSelect.style.backgroundColor = 'var(--color-bg-secondary)';
                        budgetSelect.style.color = 'var(--color-text-secondary)';
                        budgetSelect.style.cursor = 'not-allowed';
                        budgetSelect.style.opacity = '0.6';
                        budgetSelect.style.pointerEvents = 'none';
                        console.log('❌ Budget field DISABLED');
                    }
                }
                
                // Initial update
                updateBudgetField();
                
                // Add event listener
                typeSelect.addEventListener('change', function(e) {
                    console.log('Type changed event fired:', e.target.value);
                    updateBudgetField();
                });
                
                // Also listen for input event
                typeSelect.addEventListener('input', function(e) {
                    console.log('Type input event fired:', e.target.value);
                    updateBudgetField();
                });
            }
            
            // Try multiple ways to ensure it runs
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initBudgetField);
            } else {
                // DOM already loaded
                initBudgetField();
            }
            
            // Also try after a short delay as fallback
            setTimeout(initBudgetField, 100);
        </script>
    @endpush
</x-app-layout>


