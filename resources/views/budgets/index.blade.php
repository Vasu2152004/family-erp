<x-app-layout title="Budgets: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Budgets']
        ]" />

        <!-- Header -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Budgets</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage monthly budgets for {{ $family->name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('create', [\App\Models\Budget::class, $family])
                        <a href="{{ route('finance.budgets.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Create Budget</x-button>
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Budgets List -->
        @if($budgetsWithStatus->count() > 0)
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <div class="mb-4">
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        Showing budgets for {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }}
                    </p>
                </div>
                <div class="space-y-4">
                    @foreach($budgetsWithStatus as $item)
                        @php
                            $budget = $item['budget'];
                            $status = $item['status'];
                            $percentage = $status['percentage'];
                        @endphp
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">
                                        {{ $budget->category ? $budget->category->name : 'Total Budget' }}
                                        @if($budget->isPersonal())
                                            <span class="text-xs font-normal text-[var(--color-text-secondary)]">({{ $budget->familyMember->user->name ?? 'Personal' }})</span>
                                        @else
                                            <span class="text-xs font-normal text-[var(--color-text-secondary)]">(Family)</span>
                                        @endif
                                    </h3>
                                    <p class="text-sm text-[var(--color-text-secondary)]">
                                        Budget: {{ number_format($budget->amount, 2) }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    @if($status['is_exceeded'])
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Exceeded</span>
                                    @elseif($percentage >= ($budget->alert_threshold ?? 100))
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Alert</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">On Track</span>
                                    @endif
                                    @can('update', $budget)
                                        <a href="{{ route('finance.budgets.edit', ['budget' => $budget->id, 'family_id' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)] text-sm">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('delete', $budget)
                                        <form action="{{ route('finance.budgets.destroy', ['budget' => $budget->id, 'family_id' => $family->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this budget?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-[var(--color-text-secondary)]">Spent: {{ number_format($status['spent'], 2) }}</span>
                                    <span class="text-[var(--color-text-secondary)]">Remaining: {{ number_format($status['remaining'], 2) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full {{ $status['is_exceeded'] ? 'bg-red-600' : ($percentage >= ($budget->alert_threshold ?? 100) ? 'bg-yellow-500' : 'bg-green-600') }}" style="width: {{ min(100, $percentage) }}%"></div>
                                </div>
                                <p class="text-xs text-[var(--color-text-tertiary)] mt-1">{{ number_format($percentage, 1) }}% used</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-12 text-center">
                <p class="text-[var(--color-text-secondary)] mb-4">No budgets created for this month yet.</p>
                @can('create', [\App\Models\Budget::class, $family])
                    <a href="{{ route('families.budgets.create', $family) }}">
                        <x-button variant="primary" size="md">Create First Budget</x-button>
                    </a>
                @endcan
            </div>
        @endif

        <!-- Budget vs Actual Spending Chart -->
        @if(count($budgetVsActualData ?? []) > 0)
            <div class="card">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Budget vs Actual Spending ({{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }})</h2>
                <div id="budgetVsActualChart" style="min-height: 400px;"></div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
        <script src="{{ asset('js/budget-charts.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Budget vs Actual Data
                const budgetVsActualData = @json($budgetVsActualData ?? []);
                
                // Initialize charts once ApexCharts is loaded
                if (typeof ApexCharts !== 'undefined' && typeof initBudgetCharts === 'function') {
                    initBudgetCharts(budgetVsActualData);
                } else {
                    // Wait for ApexCharts to load
                    window.addEventListener('load', function() {
                        if (typeof ApexCharts !== 'undefined' && typeof initBudgetCharts === 'function') {
                            initBudgetCharts(budgetVsActualData);
                        } else {
                            console.error('ApexCharts or initBudgetCharts function not available');
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>


