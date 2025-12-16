<x-app-layout title="Finance Analytics: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Analytics']
        ]" />

        <!-- Header -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Finance Analytics</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Financial insights and analytics for {{ $family->name }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Account Balances Summary -->
        @if(count($accountBalances) > 0)
            <div class="card">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Account Balances</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($accountBalances as $account)
                        <div class="bg-[var(--color-surface-alt)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                            <p class="text-sm text-[var(--color-text-secondary)]">{{ $account['account_name'] }}</p>
                            <p class="text-xl font-bold {{ $account['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($account['balance'], 2) }}
                            </p>
                            <p class="text-xs text-[var(--color-text-tertiary)]">{{ $account['account_type'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Monthly Expenses Chart -->
        <div class="card">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Monthly Expenses & Income ({{ $currentYear }})</h2>
            <div id="monthlyChart" style="min-height: 400px;"></div>
        </div>

        <!-- Member-wise Spending -->
        <div class="card">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Member-wise Spending ({{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }})</h2>
            @if(count($memberWiseData) > 0)
                <div id="memberWiseChart" style="min-height: 400px;"></div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)]">No spending data available for this month.</p>
                </div>
            @endif
        </div>

        <!-- Category-wise Expenses -->
        @if(count($categoryWiseData) > 0)
            <div class="card">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Category-wise Expenses ({{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }})</h2>
                <div id="categoryWiseChart" style="min-height: 400px;"></div>
            </div>
        @endif

        <!-- Savings Trend Chart -->
        <div class="card">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Savings Trend ({{ $currentYear }})</h2>
            <div id="savingsTrendChart" style="min-height: 400px;"></div>
        </div>

        <!-- Account Balance Trends -->
        @if(count($accountBalanceTrends) > 0)
            <div class="card">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Account Balance Trends ({{ $currentYear }})</h2>
                <div id="accountBalanceTrendsChart" style="min-height: 400px;"></div>
            </div>
        @endif

        <!-- Income Sources Breakdown -->
        @if(count($incomeSourcesData) > 0)
            <div class="card">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Income Sources ({{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }})</h2>
                <div id="incomeSourcesChart" style="min-height: 400px;"></div>
            </div>
        @endif

        <!-- Expense Patterns by Day of Week -->
        @if(count($expensePatternsData) > 0)
            <div class="card">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Expense Patterns by Day of Week ({{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }})</h2>
                <div id="expensePatternsChart" style="min-height: 400px;"></div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
        <script src="{{ asset('js/finance-charts.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Monthly Chart Data
                const monthlyData = @json($monthlyData ?? []);
                // Member-wise Data
                const memberWiseData = @json($memberWiseData ?? []);
                // Category-wise Data
                const categoryWiseData = @json($categoryWiseData ?? []);
                // Savings Trend Data
                const savingsTrendData = @json($savingsTrendData ?? []);
                // Account Balance Trends Data
                const accountBalanceTrends = @json($accountBalanceTrends ?? []);
                // Income Sources Data
                const incomeSourcesData = @json($incomeSourcesData ?? []);
                // Expense Patterns Data
                const expensePatternsData = @json($expensePatternsData ?? []);
                
                // Initialize charts once ApexCharts is loaded
                if (typeof ApexCharts !== 'undefined' && typeof initFinanceCharts === 'function') {
                    initFinanceCharts(monthlyData, memberWiseData, categoryWiseData, savingsTrendData, accountBalanceTrends, incomeSourcesData, expensePatternsData);
                } else {
                    // Wait for ApexCharts to load
                    window.addEventListener('load', function() {
                        if (typeof ApexCharts !== 'undefined' && typeof initFinanceCharts === 'function') {
                            initFinanceCharts(monthlyData, memberWiseData, categoryWiseData, savingsTrendData, accountBalanceTrends, incomeSourcesData, expensePatternsData);
                        } else {
                            console.error('ApexCharts or initFinanceCharts function not available');
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>


