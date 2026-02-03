<x-app-layout title="Dashboard">
    <x-breadcrumb :items="[
        ['label' => 'Dashboard']
    ]" />

    <!-- Welcome Section - Compact -->
    <div class="mb-6">
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-xl shadow-lg p-5 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20"></div>
            <div class="relative z-10">
                <h1 class="text-xl sm:text-2xl font-bold truncate">Welcome back, {{ $user->name }}! 👋</h1>
                <p class="text-blue-100 text-sm mt-0.5">Here's what's happening with your families today.</p>
            </div>
        </div>
    </div>

    <!-- Stats Grid - Compact, responsive: 2 cols mobile, 3 sm, 4 md, 5 lg, 6 xl -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 mb-6 w-full min-w-0">
        <a href="{{ route('families.index') }}" class="group bg-white rounded-xl shadow border border-gray-200 p-3 hover:shadow-lg hover:scale-[1.01] transition-all duration-300 min-w-0">
            <div class="flex items-center justify-between mb-1">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow group-hover:scale-110 transition-transform shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 truncate">{{ $familiesCount }}</h3>
            <p class="text-xs text-gray-600">Families</p>
        </a>

        <a href="{{ route('family-member-requests.index') }}" class="group bg-white rounded-xl shadow border border-gray-200 p-3 hover:shadow-lg hover:scale-[1.01] transition-all duration-300 relative min-w-0">
            @if($pendingRequestsCount > 0)
                <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full animate-pulse">{{ $pendingRequestsCount }}</span>
            @endif
            <div class="flex items-center justify-between mb-1">
                <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center shadow group-hover:scale-110 transition-transform shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-purple-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 truncate">{{ $pendingRequestsCount }}</h3>
            <p class="text-xs text-gray-600">Pending Requests</p>
        </a>

        @if($familiesCount > 0)
            @if(!empty($financeSummary))
                <a href="{{ route('finance.index', ['family_id' => $financeSummary[0]['family']->id]) }}" class="group bg-white rounded-xl shadow border border-gray-200 p-3 hover:shadow-lg hover:scale-[1.01] transition-all duration-300 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <div class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center shadow group-hover:scale-110 transition-transform shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-emerald-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 truncate">₹{{ number_format($financeSummary[0]['total_balance'], 0) }}</h3>
                    <p class="text-xs text-gray-600">Total Balance</p>
                </a>
            @endif

            @if($firstFamily)
                <a href="{{ route('families.inventory.items.index', ['family' => $firstFamily->id]) }}" class="group bg-white rounded-xl shadow border border-gray-200 p-3 hover:shadow-lg hover:scale-[1.01] transition-all duration-300 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center shadow group-hover:scale-110 transition-transform shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-amber-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 truncate">{{ $lowStockCount }}</h3>
                    <p class="text-xs text-gray-600">Low Stock Items</p>
                </a>
            @endif

            @if($firstFamily)
                <a href="{{ route('shopping-list.index', ['family_id' => $firstFamily->id]) }}" class="group bg-white rounded-xl shadow border border-gray-200 p-3 hover:shadow-lg hover:scale-[1.01] transition-all duration-300 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <div class="w-8 h-8 bg-gradient-to-br from-lime-500 to-green-600 rounded-lg flex items-center justify-center shadow group-hover:scale-110 transition-transform shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-lime-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 truncate">{{ $shoppingListPendingCount }}</h3>
                    <p class="text-xs text-gray-600">Shopping List</p>
                </a>
            @endif

            @php $totalTasks = ($taskCountsByStatus['pending'] ?? 0) + ($taskCountsByStatus['in_progress'] ?? 0) + ($taskCountsByStatus['done'] ?? 0); @endphp
            @if($totalTasks > 0 && $firstFamily)
                <a href="{{ route('families.tasks.index', ['family' => $firstFamily->id]) }}" class="group bg-white rounded-xl shadow border border-gray-200 p-3 hover:shadow-lg hover:scale-[1.01] transition-all duration-300 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <div class="w-8 h-8 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-lg flex items-center justify-center shadow group-hover:scale-110 transition-transform shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-cyan-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 truncate">{{ $taskCountsByStatus['pending'] + $taskCountsByStatus['in_progress'] }}</h3>
                    <p class="text-xs text-gray-600">Active Tasks</p>
                </a>
            @endif
        @endif

        @if($familiesCount === 0)
            <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-4 xl:col-span-5 2xl:col-span-6 bg-white rounded-xl shadow border border-gray-200 p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Get Started</h3>
                    <p class="text-sm text-gray-600 mb-3">Create your first family to access all features.</p>
                    <a href="{{ route('families.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Create Family
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Content: Recent Activity (full width, no alerts at top) -->
    <div class="space-y-6 mb-6">
            @if($familiesCount > 0)
            <!-- Recent Transactions -->
            @if($recentTransactions->isNotEmpty())
            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800">Recent Transactions</h2>
                    <a href="{{ route('finance.index', $firstFamily ? ['family_id' => $firstFamily->id] : []) }}" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">View Finance</a>
                </div>
                <div class="space-y-2">
                    @foreach($recentTransactions as $txn)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="font-medium text-gray-800 text-sm">{{ $txn->description ?: $txn->type }}</p>
                                <p class="text-xs text-gray-500">{{ $txn->family->name ?? '' }} · {{ $txn->transaction_date->format('M d, Y') }}</p>
                            </div>
                            <span class="font-semibold {{ $txn->type === 'INCOME' ? 'text-emerald-600' : ($txn->type === 'EXPENSE' ? 'text-red-600' : 'text-gray-600') }}">
                                {{ $txn->type === 'INCOME' ? '+' : ($txn->type === 'EXPENSE' ? '-' : '') }}₹{{ number_format($txn->amount, 0) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Finance Summary -->
            @if(!empty($financeSummary))
            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Finance Summary</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($financeSummary as $summary)
                        <a href="{{ route('finance.index', ['family_id' => $summary['family']->id]) }}" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors">
                            <p class="text-sm font-medium text-gray-600">{{ $summary['family']->name }}</p>
                            <p class="text-xl font-bold text-gray-800 mt-1">₹{{ number_format($summary['total_balance'], 0) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Income: ₹{{ number_format($summary['monthly_income'], 0) }} · Expense: ₹{{ number_format($summary['monthly_expense'], 0) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Upcoming Events & Doctor Visits -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($upcomingEvents->isNotEmpty())
                <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800">Upcoming Events</h2>
                        @if($firstFamily)
                            <a href="{{ route('families.calendar.index', ['family' => $firstFamily->id]) }}" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">View Calendar</a>
                        @endif
                    </div>
                    <div class="space-y-2">
                        @foreach($upcomingEvents->take(5) as $event)
                            <div class="py-2 border-b border-gray-100 last:border-0">
                                <p class="font-medium text-gray-800 text-sm">{{ $event->title }}</p>
                                <p class="text-xs text-gray-500">{{ $event->start_at->format('M d, g:i A') }} · {{ $event->family->name ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($upcomingDoctorVisits->isNotEmpty())
                <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800">Upcoming Doctor Visits</h2>
                        @if($firstFamily)
                            <a href="{{ route('families.health.visits.index', ['family' => $firstFamily->id]) }}" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">View All</a>
                        @endif
                    </div>
                    <div class="space-y-2">
                        @foreach($upcomingDoctorVisits->take(5) as $visit)
                            @php $visitDate = $visit->next_visit_date ?? $visit->visit_date; @endphp
                            <div class="py-2 border-b border-gray-100 last:border-0">
                                <p class="font-medium text-gray-800 text-sm">{{ $visit->doctor_name ?? 'Doctor Visit' }}</p>
                                <p class="text-xs text-gray-500">{{ $visit->familyMember ? $visit->familyMember->first_name . ' ' . $visit->familyMember->last_name : '' }} · {{ $visitDate?->format('M d, Y') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Low Stock & Task Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($lowStockItems->isNotEmpty())
                <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800">Low Stock Items</h2>
                        <a href="{{ route('families.inventory.items.index', ['family' => $lowStockItems->first()->family_id]) }}" class="text-sm text-amber-600 hover:text-amber-800 font-semibold">View Inventory</a>
                    </div>
                    <ul class="space-y-2">
                        @foreach($lowStockItems as $item)
                            <li class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                                <span class="font-medium text-gray-800 text-sm">{{ $item->name }}</span>
                                <span class="text-xs text-amber-600 font-semibold">{{ $item->family->name ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @php $totalTasks = ($taskCountsByStatus['pending'] ?? 0) + ($taskCountsByStatus['in_progress'] ?? 0) + ($taskCountsByStatus['done'] ?? 0); @endphp
                @if($totalTasks > 0 && $firstFamily)
                <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800">Task Summary</h2>
                        <a href="{{ route('families.tasks.index', ['family' => $firstFamily->id]) }}" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">View Tasks</a>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                        <div class="flex-1 min-w-0 text-center p-3 rounded-lg bg-amber-50">
                            <p class="text-2xl font-bold text-amber-600">{{ $taskCountsByStatus['pending'] ?? 0 }}</p>
                            <p class="text-xs text-gray-600">Pending</p>
                        </div>
                        <div class="flex-1 min-w-0 text-center p-3 rounded-lg bg-blue-50">
                            <p class="text-2xl font-bold text-blue-600">{{ $taskCountsByStatus['in_progress'] ?? 0 }}</p>
                            <p class="text-xs text-gray-600">In Progress</p>
                        </div>
                        <div class="flex-1 min-w-0 text-center p-3 rounded-lg bg-emerald-50">
                            <p class="text-2xl font-bold text-emerald-600">{{ $taskCountsByStatus['done'] ?? 0 }}</p>
                            <p class="text-xs text-gray-600">Done</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
    </div>

    <!-- All Notifications - At Bottom -->
    @php
        $hasAnyAlerts = $budgetAlerts->count() > 0 || $expiringVehicles->count() > 0 || $otherNotifications->count() > 0;
    @endphp
    @if($hasAnyAlerts)
    <div class="bg-white rounded-xl shadow border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-800">All Notifications</h2>
            <a href="{{ route('notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">View All</a>
        </div>
        <div class="space-y-3 max-h-80 overflow-y-auto">
            @foreach($budgetAlerts as $alert)
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-red-800">{{ $alert->title }}</h3>
                            <p class="text-sm text-gray-700 mt-1">{{ $alert->message }}</p>
                            @if($alert->data && isset($alert->data['family_id']) && $family = $familiesById->get($alert->data['family_id']))
                                <a href="{{ route('finance.budgets.index', ['family_id' => $family->id]) }}" class="text-xs text-red-600 hover:text-red-800 font-semibold mt-2 inline-block">View Budgets →</a>
                            @endif
                        </div>
                        <x-form method="POST" action="{{ route('notifications.read', $alert) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600 p-1" title="Dismiss">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </x-form>
                    </div>
                </div>
            @endforeach
            @foreach($expiringVehicles as $vehicle)
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-lg">
                    <h3 class="font-semibold text-amber-800">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->registration_number }})</h3>
                    <p class="text-sm text-gray-700 mt-1">Vehicle documents expiring in next 30 days</p>
                    <a href="{{ route('families.vehicles.show', ['family' => $vehicle->family_id, 'vehicle' => $vehicle->id]) }}" class="text-xs text-amber-600 hover:text-amber-800 font-semibold mt-2 inline-block">View Vehicle →</a>
                </div>
            @endforeach
            @foreach($otherNotifications as $notification)
                <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-800">{{ $notification->title }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                        </div>
                        <x-form method="POST" action="{{ route('notifications.read', $notification) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600 p-1" title="Dismiss">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </x-form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 min-w-0">
        <a href="{{ route('families.index') }}" class="group bg-white rounded-xl shadow border border-gray-200 p-4 sm:p-5 hover:shadow-lg hover:scale-[1.01] transition-all duration-300 min-w-0">
            <div class="flex flex-col sm:flex-row items-start gap-3 sm:space-x-4 sm:gap-0">
                <div class="w-14 h-14 shrink-0 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow group-hover:scale-110 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">Manage Families</h3>
                    <p class="text-gray-600 mb-4 text-sm sm:text-base">View and manage all your family groups, members, and roles in one place.</p>
                    <span class="inline-flex items-center text-blue-600 font-semibold text-sm group-hover:translate-x-1 transition-transform">
                        View Families
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </span>
                </div>
            </div>
        </a>

        <a href="{{ route('family-member-requests.index') }}" class="group bg-white rounded-xl shadow border border-gray-200 p-4 sm:p-5 hover:shadow-lg hover:scale-[1.01] transition-all duration-300 min-w-0">
            <div class="flex flex-col sm:flex-row items-start gap-3 sm:space-x-4 sm:gap-0">
                <div class="w-14 h-14 shrink-0 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow group-hover:scale-110 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2 group-hover:text-purple-600 transition-colors">Family Requests</h3>
                    <p class="text-gray-600 mb-4 text-sm sm:text-base">Review and respond to family member requests from other users.</p>
                    <span class="inline-flex items-center text-purple-600 font-semibold text-sm group-hover:translate-x-1 transition-transform">
                        View Requests
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </span>
                </div>
            </div>
        </a>
    </div>
</x-app-layout>

