<x-app-layout title="Edit Transaction: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Transactions', 'url' => route('finance.transactions.index', ['family_id' => $family->id])],
            ['label' => 'Edit Transaction']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Edit Transaction</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Update transaction for {{ $family->name }}
                </p>
            </div>

        <x-form method="PUT" action="{{ route('finance.transactions.update', ['transaction' => $transaction->id, 'family_id' => $family->id]) }}" class="space-y-6">
            <input type="hidden" name="family_id" value="{{ $family->id }}">

            <div>
                <x-label for="type" required>Transaction Type</x-label>
                <select name="type" id="type" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="INCOME" {{ old('type', $transaction->type) == 'INCOME' ? 'selected' : '' }}>Income</option>
                    <option value="EXPENSE" {{ old('type', $transaction->type) == 'EXPENSE' ? 'selected' : '' }}>Expense</option>
                </select>
                <x-error-message field="type" />
            </div>

            <div>
                <x-label for="finance_account_id" required>Account</x-label>
                <select name="finance_account_id" id="finance_account_id" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('finance_account_id', $transaction->finance_account_id) == $account->id ? 'selected' : '' }}>{{ $account->name }} ({{ $account->type }})</option>
                    @endforeach
                </select>
                <x-error-message field="finance_account_id" />
            </div>

            <div>
                <x-label for="amount" required>Amount</x-label>
                <x-input 
                    type="number" 
                    name="amount" 
                    id="amount" 
                    value="{{ old('amount', $transaction->amount) }}" 
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
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $transaction->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }} ({{ $category->type }})</option>
                    @endforeach
                </select>
                <x-error-message field="category_id" />
            </div>

            <div>
                <x-label for="family_member_id">Family Member</x-label>
                <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="">Select member...</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" {{ old('family_member_id', $transaction->family_member_id) == $member->id ? 'selected' : '' }}>{{ $member->first_name }} {{ $member->last_name }}</option>
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
                    value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" 
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
                >{{ old('description', $transaction->description) }}</textarea>
                <x-error-message field="description" />
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_shared" id="is_shared" value="1" {{ old('is_shared', $transaction->is_shared) ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                <x-label for="is_shared" class="ml-2">Share with other members</x-label>
            </div>

            <div id="budget_field" style="display: {{ $transaction->type === 'EXPENSE' ? 'block' : 'none' }};">
                <x-label for="budget_id">Assign to Specific Budget (Optional)</x-label>
                <select name="budget_id" id="budget_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="">No specific budget (will not affect any budget)</option>
                    @foreach($budgets as $budget)
                        <option value="{{ $budget->id }}" {{ old('budget_id', $transaction->budget_id) == $budget->id ? 'selected' : '' }}>
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
                </select>
                <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Select a specific budget this expense will be deducted from. If not selected, the expense won't affect any budget.</p>
                <x-error-message field="budget_id" />
            </div>

            <div class="flex gap-4">
                <x-button type="submit" variant="primary" size="md">
                    Update Transaction
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
        <script src="{{ asset('js/transaction-form.js') }}"></script>
    @endpush
</x-app-layout>


