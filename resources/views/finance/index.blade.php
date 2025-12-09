<x-app-layout title="Finance & Expenses">
    <div class="space-y-6">
        @if($activeFamily)
            <x-breadcrumb :items="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => $activeFamily->name, 'url' => route('families.show', $activeFamily)],
                ['label' => 'Finance']
            ]" />
        @endif

        <!-- Family Selector (if multiple families) -->
        @if($accessibleFamilies->count() > 1)
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                <label for="family-selector" class="block text-sm font-medium text-[var(--color-text-primary)] mb-2">
                    Select Family
                </label>
                <select 
                    id="family-selector" 
                    class="w-full md:w-auto px-4 py-2 border border-[var(--color-border-primary)] rounded-lg bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
                    onchange="window.location.href = '{{ route('finance.index') }}?family_id=' + this.value"
                >
                    @foreach($accessibleFamilies as $familyOption)
                        <option value="{{ $familyOption->id }}" {{ $activeFamily && $familyOption->id === $activeFamily->id ? 'selected' : '' }}>
                            {{ $familyOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($activeFamily)
            @php
                $userRole = \App\Models\FamilyUserRole::where('family_id', $activeFamily->id)
                    ->where('user_id', Auth::id())
                    ->first();
                $isFamilyMember = \App\Models\FamilyMember::where('family_id', $activeFamily->id)
                    ->where('user_id', Auth::id())
                    ->exists();
                // User is a "member" if they have MEMBER role OR they are a family member without a role
                $isMember = ($userRole && $userRole->role === 'MEMBER') || ($isFamilyMember && !$userRole);
                $isAdminOrOwner = $userRole && in_array($userRole->role, ['OWNER', 'ADMIN']);
            @endphp

            <!-- Finance & Expenses Dashboard -->
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Finance & Expenses</h1>
                        <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                            @if($isMember)
                                Enter your income and expenses for {{ $activeFamily->name }}
                            @else
                                Manage finances for {{ $activeFamily->name }}
                            @endif
                        </p>
                    </div>
                    @if($isAdminOrOwner)
                        <div class="flex gap-2">
                            @can('viewAny', [\App\Models\Transaction::class, $activeFamily])
                                <a href="{{ route('finance.analytics.dashboard', ['family_id' => $activeFamily->id]) }}">
                                    <x-button variant="outline" size="md">Analytics</x-button>
                                </a>
                            @endcan
                            @can('viewAny', [\App\Models\FinanceAccount::class, $activeFamily])
                                <a href="{{ route('finance.accounts.index', ['family_id' => $activeFamily->id]) }}">
                                    <x-button variant="primary" size="md">Finance</x-button>
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>
                
                @if($isMember)
                    <!-- Member View - Simple Transaction Entry -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('finance.transactions.create', ['family_id' => $activeFamily->id, 'type' => 'INCOME']) }}" class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-8 border-2 border-green-200 hover:shadow-lg hover:border-green-400 transition-all">
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-green-600 font-medium mb-1">Add Income</p>
                                    <p class="text-2xl font-bold text-green-700">Enter Income</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">Record money received or earned</p>
                        </a>
                        
                        <a href="{{ route('finance.transactions.create', ['family_id' => $activeFamily->id, 'type' => 'EXPENSE']) }}" class="bg-gradient-to-br from-red-50 to-orange-50 rounded-lg p-8 border-2 border-red-200 hover:shadow-lg hover:border-red-400 transition-all">
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-red-600 font-medium mb-1">Add Expense</p>
                                    <p class="text-2xl font-bold text-red-700">Enter Expense</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">Record money spent or paid</p>
                        </a>
                    </div>
                    
                    <div class="mt-6">
                        <a href="{{ route('finance.transactions.index', ['family_id' => $activeFamily->id]) }}" class="block bg-[var(--color-bg-secondary)] rounded-lg p-6 border border-[var(--color-border-primary)] hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-[var(--color-text-secondary)] mb-1">View My Transactions</p>
                                    <p class="text-lg font-bold text-[var(--color-text-primary)]">Transaction History</p>
                                </div>
                                <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    </div>
                @else
                    <!-- Admin/Owner View - Full Management -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @can('viewAny', [\App\Models\FinanceAccount::class, $activeFamily])
                            <a href="{{ route('finance.accounts.index', ['family_id' => $activeFamily->id]) }}" class="bg-[var(--color-bg-secondary)] rounded-lg p-6 border border-[var(--color-border-primary)] hover:shadow-md transition-shadow">
                                <p class="text-sm text-[var(--color-text-secondary)] mb-1">Accounts</p>
                                <p class="text-lg font-bold text-[var(--color-text-primary)]">Manage</p>
                            </a>
                        @endcan
                        @can('viewAny', [\App\Models\Transaction::class, $activeFamily])
                            <a href="{{ route('finance.transactions.index', ['family_id' => $activeFamily->id]) }}" class="bg-[var(--color-bg-secondary)] rounded-lg p-6 border border-[var(--color-border-primary)] hover:shadow-md transition-shadow">
                                <p class="text-sm text-[var(--color-text-secondary)] mb-1">Transactions</p>
                                <p class="text-lg font-bold text-[var(--color-text-primary)]">View All</p>
                            </a>
                        @endcan
                        @can('viewAny', [\App\Models\Budget::class, $activeFamily])
                            <a href="{{ route('finance.budgets.index', ['family_id' => $activeFamily->id]) }}" class="bg-[var(--color-bg-secondary)] rounded-lg p-6 border border-[var(--color-border-primary)] hover:shadow-md transition-shadow">
                                <p class="text-sm text-[var(--color-text-secondary)] mb-1">Budgets</p>
                                <p class="text-lg font-bold text-[var(--color-text-primary)]">Manage</p>
                            </a>
                        @endcan
                    </div>
                @endif

                <!-- Person-wise Expense Panel -->
                @if($memberWiseExpenses && count($memberWiseExpenses) > 0)
                    <div class="mt-8">
                        <h2 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">
                            @if($isAdminOrOwner)
                                Total Expenses by Member ({{ \Carbon\Carbon::create(now()->year, now()->month, 1)->format('F Y') }})
                            @else
                                My Expenses ({{ \Carbon\Carbon::create(now()->year, now()->month, 1)->format('F Y') }})
                            @endif
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($memberWiseExpenses as $memberExpense)
                                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-6 border border-[var(--color-border-primary)]">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">
                                            {{ $memberExpense['member_name'] }}
                                        </h3>
                                    </div>
                                    <p class="text-2xl font-bold text-red-600">
                                        {{ number_format($memberExpense['amount'], 2) }}
                                    </p>
                                    <p class="text-xs text-[var(--color-text-secondary)] mt-1">Total expenses this month</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-12 text-center">
                <p class="text-[var(--color-text-secondary)] mb-4">No family selected. Please create or join a family to access finance features.</p>
                <a href="{{ route('families.index') }}">
                    <x-button variant="primary" size="md">Go to Families</x-button>
                </a>
            </div>
        @endif
    </div>
</x-app-layout>

