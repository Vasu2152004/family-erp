<x-app-layout title="Investments: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Investments']
        ]" />

        <!-- Header -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Investments</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage family investments and assets for {{ $family->name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('create', [\App\Models\Investment::class, $family])
                        <a href="{{ route('investments.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Investment</x-button>
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('investments.index', ['family_id' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <x-label for="investment_type">Investment Type</x-label>
                    <select name="investment_type" id="investment_type" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Types</option>
                        <option value="FD" {{ request('investment_type') == 'FD' ? 'selected' : '' }}>Fixed Deposit (FD)</option>
                        <option value="RD" {{ request('investment_type') == 'RD' ? 'selected' : '' }}>Recurring Deposit (RD)</option>
                        <option value="SIP" {{ request('investment_type') == 'SIP' ? 'selected' : '' }}>SIP</option>
                        <option value="MUTUAL_FUND" {{ request('investment_type') == 'MUTUAL_FUND' ? 'selected' : '' }}>Mutual Fund</option>
                        <option value="STOCK" {{ request('investment_type') == 'STOCK' ? 'selected' : '' }}>Stock</option>
                        <option value="CRYPTO" {{ request('investment_type') == 'CRYPTO' ? 'selected' : '' }}>Crypto</option>
                        <option value="OTHER" {{ request('investment_type') == 'OTHER' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <x-label for="family_member_id">Owner</x-label>
                    <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Members</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" {{ request('family_member_id') == $member->id ? 'selected' : '' }}>
                                {{ $member->first_name }} {{ $member->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-label for="show_hidden">Show Hidden</x-label>
                    <select name="show_hidden" id="show_hidden" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">Visible Only</option>
                        <option value="1" {{ request('show_hidden') == '1' ? 'selected' : '' }}>Include Hidden</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <x-button type="submit" variant="outline" size="md">Apply Filters</x-button>
                    <a href="{{ route('investments.index', ['family_id' => $family->id]) }}" class="ml-2">
                        <x-button type="button" variant="outline" size="md">Clear</x-button>
                    </a>
                </div>
            </form>
        </div>

        <!-- Investments List -->
        @if($investments->count() > 0)
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Owner</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Created By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Current Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($investments as $investment)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-[var(--color-text-primary)]">{{ $investment->name }}</span>
                                            @if($investment->is_hidden)
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Hidden</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ str_replace('_', ' ', $investment->investment_type) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $investment->owner_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $investment->createdBy?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-primary)]">
                                        ₹{{ number_format($investment->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-primary)]">
                                        {{ $investment->current_value ? '₹' . number_format($investment->current_value, 2) : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($investment->is_hidden)
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Hidden</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Visible</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            @can('view', $investment)
                                                <a href="{{ route('investments.show', ['investment' => $investment->id, 'family_id' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                                                    View
                                                </a>
                                            @endcan
                                            @can('update', $investment)
                                                <a href="{{ route('investments.edit', ['investment' => $investment->id, 'family_id' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                                                    Edit
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $investments->links() }}
                </div>
            </div>
        @else
            <div class="card">
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No investments found.</p>
                    @can('create', [\App\Models\Investment::class, $family])
                        <a href="{{ route('investments.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add First Investment</x-button>
                        </a>
                    @endcan
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

