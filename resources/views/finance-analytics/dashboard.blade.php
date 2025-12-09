<x-app-layout title="Finance Analytics: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Analytics']
        ]" />

        <!-- Header -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
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
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Account Balances</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($accountBalances as $account)
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
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
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Monthly Expenses & Income ({{ $currentYear }})</h2>
            <canvas id="monthlyChart" height="100"></canvas>
        </div>

        <!-- Member-wise Spending -->
        @if(count($memberWiseData) > 0)
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Member-wise Spending ({{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }})</h2>
                <canvas id="memberWiseChart" height="100"></canvas>
            </div>
        @endif

        <!-- Category-wise Expenses -->
        @if(count($categoryWiseData) > 0)
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Category-wise Expenses ({{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }})</h2>
                <canvas id="categoryWiseChart" height="100"></canvas>
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script src="{{ asset('js/finance-charts.js') }}"></script>
        <script>
            // Monthly Chart Data
            const monthlyData = @json($monthlyData);
            // Member-wise Data
            const memberWiseData = @json($memberWiseData);
            // Category-wise Data
            const categoryWiseData = @json($categoryWiseData);
            
            // Initialize charts
            if (typeof initFinanceCharts === 'function') {
                initFinanceCharts(monthlyData, memberWiseData, categoryWiseData);
            }
        </script>
    @endpush
</x-app-layout>


