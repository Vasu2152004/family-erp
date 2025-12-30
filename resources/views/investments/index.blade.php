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
            <x-form method="GET" action="{{ route('investments.index', ['family_id' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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
            </x-form>
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
                                            @can('delete', $investment)
                                                <x-form 
                                                    method="POST" 
                                                    action="{{ route('investments.destroy', ['investment' => $investment->id, 'family_id' => $family->id]) }}" 
                                                    class="inline"
                                                    data-confirm="Are you sure you want to delete this investment? This action cannot be undone."
                                                    data-confirm-title="Delete Investment"
                                                    data-confirm-variant="danger"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
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

        <!-- Charts Section - Moved to bottom after the list -->
        @if($investments->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Investment Type Distribution Chart (Donut) -->
                <div class="card">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Investment Type Distribution</h2>
                    @if(count($typeDistributionData) > 0)
                        <div id="investmentTypeDistributionChart" style="min-height: 400px;"></div>
                    @else
                        <div class="text-center py-12 text-[var(--color-text-secondary)]">
                            <p>No visible investments available for chart visualization.</p>
                            <p class="text-sm mt-2">Hidden investments are excluded from charts.</p>
                        </div>
                    @endif
                </div>

                <!-- Owner-wise Distribution Chart (Donut) -->
                <div class="card">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Owner-wise Distribution</h2>
                    @if(count($ownerDistributionData) > 0)
                        <div id="investmentOwnerDistributionChart" style="min-height: 400px;"></div>
                    @else
                        <div class="text-center py-12 text-[var(--color-text-secondary)]">
                            <p>No visible investments available for chart visualization.</p>
                            <p class="text-sm mt-2">Hidden investments are excluded from charts.</p>
                        </div>
                    @endif
                </div>

                <!-- Investment Count by Type Chart (Bar) -->
                <div class="card">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Investment Count by Type</h2>
                    @if(count($countByTypeData) > 0)
                        <div id="investmentCountByTypeChart" style="min-height: 400px;"></div>
                    @else
                        <div class="text-center py-12 text-[var(--color-text-secondary)]">
                            <p>No visible investments available for chart visualization.</p>
                            <p class="text-sm mt-2">Hidden investments are excluded from charts.</p>
                        </div>
                    @endif
                </div>

                <!-- Profit/Loss Trend Chart (Column) -->
                @if(count($profitLossTrendData) > 0)
                    <div class="card lg:col-span-2">
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Profit/Loss Trend</h2>
                        <div id="investmentProfitLossTrendChart" style="min-height: 400px;"></div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if($investments->count() > 0 && (count($typeDistributionData ?? []) > 0 || count($profitLossTrendData ?? []) > 0 || count($ownerDistributionData ?? []) > 0 || count($countByTypeData ?? []) > 0))
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
            <script src="{{ asset('js/investment-charts.js') }}"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Investment Type Distribution Data
                    const typeDistributionData = @json($typeDistributionData ?? []);
                    // Profit/Loss Trend Data
                    const profitLossTrendData = @json($profitLossTrendData ?? []);
                    // Owner-wise Distribution Data
                    const ownerDistributionData = @json($ownerDistributionData ?? []);
                    // Count by Type Data
                    const countByTypeData = @json($countByTypeData ?? []);
                    
                    // Initialize charts once ApexCharts is loaded
                    if (typeof ApexCharts !== 'undefined' && typeof initInvestmentCharts === 'function') {
                        initInvestmentCharts(typeDistributionData, profitLossTrendData, ownerDistributionData, countByTypeData);
                    } else {
                        // Wait for ApexCharts to load
                        window.addEventListener('load', function() {
                            if (typeof ApexCharts !== 'undefined' && typeof initInvestmentCharts === 'function') {
                                initInvestmentCharts(typeDistributionData, profitLossTrendData, ownerDistributionData, countByTypeData);
                            } else {
                                console.error('ApexCharts or initInvestmentCharts function not available');
                            }
                        });
                    }
                });
            </script>
        @endpush
    @endif
</x-app-layout>

