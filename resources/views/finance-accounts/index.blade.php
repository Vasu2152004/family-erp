<x-app-layout title="Finance Accounts: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Finance Accounts']
        ]" />

        <!-- Header -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Finance Accounts</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage finance accounts for {{ $family->name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('create', [\App\Models\FinanceAccount::class, $family])
                        <a href="{{ route('finance.accounts.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Create Account</x-button>
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Accounts List -->
        @if($accounts->count() > 0)
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Current Balance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($accounts as $account)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-[var(--color-text-primary)]">
                                            {{ $account->name }}
                                        </div>
                                        @if($account->description)
                                            <div class="text-xs text-[var(--color-text-secondary)]">
                                                {{ $account->description }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $account->type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $account->current_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($account->current_balance, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($account->is_active)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            @can('update', $account)
                                                <a href="{{ route('finance.accounts.edit', ['account' => $account->id, 'family_id' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                                                    Edit
                                                </a>
                                            @endcan
                                            @can('delete', $account)
                                                <form action="{{ route('finance.accounts.destroy', ['account' => $account->id, 'family_id' => $family->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this account?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $accounts->links() }}
                </div>
            </div>
        @else
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-12 text-center">
                <p class="text-[var(--color-text-secondary)] mb-4">No finance accounts created yet.</p>
                @can('create', [\App\Models\FinanceAccount::class, $family])
                    <a href="{{ route('finance.accounts.create', ['family_id' => $family->id]) }}">
                        <x-button variant="primary" size="md">Create First Account</x-button>
                    </a>
                @endcan
            </div>
        @endif
    </div>
</x-app-layout>


