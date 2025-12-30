<x-app-layout title="Transactions: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Transactions']
        ]" />

        <!-- Header -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Transactions</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        View and manage transactions for {{ $family->name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('create', [\App\Models\Transaction::class, $family])
                        <a href="{{ route('finance.transactions.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Transaction</x-button>
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('finance.transactions.index', ['family_id' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <x-label for="type">Type</x-label>
                    <select name="type" id="type" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Types</option>
                        <option value="INCOME" {{ request('type') == 'INCOME' ? 'selected' : '' }}>Income</option>
                        <option value="EXPENSE" {{ request('type') == 'EXPENSE' ? 'selected' : '' }}>Expense</option>
                        <option value="TRANSFER" {{ request('type') == 'TRANSFER' ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>
                <div>
                    <x-label for="category_id">Category</x-label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-label for="family_member_id">Member</x-label>
                    <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Members</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" {{ request('family_member_id') == $member->id ? 'selected' : '' }}>{{ $member->first_name }} {{ $member->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-label for="start_date">Start Date</x-label>
                    <x-input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="mt-1" />
                </div>
                <div>
                    <x-label for="end_date">End Date</x-label>
                    <x-input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="mt-1" />
                </div>
                <div class="md:col-span-5 flex gap-2">
                    <x-button type="submit" variant="primary" size="sm">Filter</x-button>
                    <a href="{{ route('finance.transactions.index', ['family_id' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="sm">Clear</x-button>
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions List -->
        @if($transactions->count() > 0)
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Account</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Member</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Budget</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $transaction->transaction_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($transaction->type === 'INCOME') bg-green-100 text-green-800
                                            @elseif($transaction->type === 'EXPENSE') bg-red-100 text-red-800
                                            @else bg-blue-100 text-blue-800
                                            @endif">
                                            {{ $transaction->type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-primary)]">
                                        {{ $transaction->financeAccount->name }}
                                        @if($transaction->isTransfer() && $transaction->transferToAccount)
                                            <span class="text-xs text-[var(--color-text-secondary)]">→ {{ $transaction->transferToAccount->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $transaction->category?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        <div class="flex items-center gap-2">
                                            {{ $transaction->familyMember ? $transaction->familyMember->first_name . ' ' . $transaction->familyMember->last_name : 'N/A' }}
                                            @if($transaction->is_shared)
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800" title="Shared with other members">
                                                    👁️ Shared
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        @if($transaction->budget)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                                @if($transaction->budget->isPersonal())
                                                    {{ $transaction->budget->familyMember?->user?->name ?? 'Personal' }}
                                                @else
                                                    Family
                                                @endif
                                                - {{ $transaction->budget->category?->name ?? 'Total' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-[var(--color-text-secondary)]">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $transaction->type === 'INCOME' ? 'text-green-600' : ($transaction->type === 'EXPENSE' ? 'text-red-600' : 'text-blue-600') }}">
                                        {{ $transaction->type === 'EXPENSE' ? '-' : ($transaction->type === 'INCOME' ? '+' : '') }}{{ number_format($transaction->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            @can('update', $transaction)
                                                <a href="{{ route('finance.transactions.edit', ['transaction' => $transaction->id, 'family_id' => $family->id]) }}">
                                                    <x-button variant="outline" size="sm">Edit</x-button>
                                                </a>
                                            @endcan
                                            @can('delete', $transaction)
                                                <x-form 
                                                    action="{{ route('finance.transactions.destroy', ['transaction' => $transaction->id, 'family_id' => $family->id]) }}" 
                                                    method="DELETE" 
                                                    class="inline"
                                                    data-confirm="Are you sure you want to delete this transaction?"
                                                    data-confirm-title="Delete Transaction"
                                                    data-confirm-variant="danger"
                                                >
                                                    @csrf
                                                    <x-button type="submit" variant="danger-outline" size="sm">Delete</x-button>
                                                </x-form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $transactions->links() }}
                </div>
            </div>
        @else
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-12 text-center">
                <p class="text-[var(--color-text-secondary)] mb-4">No transactions found.</p>
                @can('create', [\App\Models\Transaction::class, $family])
                    <a href="{{ route('finance.transactions.create', ['family_id' => $family->id]) }}">
                        <x-button variant="primary" size="md">Add First Transaction</x-button>
                    </a>
                @endcan
            </div>
        @endif
    </div>
</x-app-layout>


