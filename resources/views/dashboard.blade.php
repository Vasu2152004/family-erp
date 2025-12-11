<x-app-layout title="Dashboard">
    <x-breadcrumb :items="[
        ['label' => 'Dashboard']
    ]" />

    @php
        $user = Auth::user();
        $familiesCount = \App\Models\FamilyUserRole::where('user_id', $user->id)
            ->pluck('family_id')
            ->merge(\App\Models\FamilyMember::where('user_id', $user->id)->pluck('family_id'))
            ->unique()
            ->count();
        $pendingRequestsCount = \App\Models\FamilyMemberRequest::where('requested_user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        
        // Get unread notifications, especially budget alerts
        $unreadNotifications = $user->unreadNotifications()->orderBy('created_at', 'desc')->get();
        $budgetAlerts = $unreadNotifications->filter(function($notification) {
            return in_array($notification->type, ['budget_alert', 'budget_exceeded']);
        });
        $otherNotifications = $unreadNotifications->filter(function($notification) {
            return !in_array($notification->type, ['budget_alert', 'budget_exceeded']);
        });
    @endphp

    <!-- Welcome Section -->
    <div class="mb-8">
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-xl p-8 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>
            <div class="relative z-10">
                <h1 class="text-4xl font-bold mb-2">Welcome back, {{ $user->name }}! 👋</h1>
                <p class="text-blue-100 text-lg">Here's what's happening with your families today.</p>
            </div>
        </div>
    </div>

    <!-- Budget Alerts - Subtle Section -->
    @if($budgetAlerts->count() > 0)
        <div class="mb-8 bg-red-50 rounded-2xl shadow-lg p-6 border border-red-200">
            <div class="bg-white rounded-xl p-6 border border-red-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center shadow-sm">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-red-600">⚠️ Budget Alerts</h2>
                            <p class="text-sm text-gray-600">Alerts needing your review</p>
                        </div>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors shadow">
                        View All ({{ $budgetAlerts->count() }})
                    </a>
                </div>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($budgetAlerts->take(3) as $alert)
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-bold text-red-800 mb-1">{{ $alert->title }}</h3>
                                    <p class="text-sm text-gray-700">{{ $alert->message }}</p>
                                    @if($alert->data && isset($alert->data['family_id']))
                                        @php
                                            $family = \App\Models\Family::find($alert->data['family_id']);
                                        @endphp
                                        @if($family)
                                            <a href="{{ route('finance.budgets.index', ['family_id' => $family->id]) }}" class="text-xs text-red-600 hover:text-red-800 font-semibold mt-2 inline-block">
                                                View Budgets →
                                            </a>
                                        @endif
                                    @endif
                                </div>
                                <form action="{{ route('notifications.read', $alert) }}" method="POST" class="ml-4">
                                    @csrf
                                    <button type="submit" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($budgetAlerts->count() > 3)
                    <div class="mt-4 text-center">
                        <a href="{{ route('notifications.index') }}" class="text-red-600 font-semibold hover:text-red-800">
                            View {{ $budgetAlerts->count() - 3 }} more alert(s) →
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Other Important Notifications -->
    @if($otherNotifications->count() > 0)
        <div class="mb-8 bg-white rounded-xl shadow-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">Other Notifications</h2>
                <a href="{{ route('notifications.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                    View All →
                </a>
            </div>
            <div class="space-y-3">
                @foreach($otherNotifications->take(3) as $notification)
                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800 mb-1">{{ $notification->title }}</h3>
                                <p class="text-sm text-gray-600">{{ $notification->message }}</p>
                            </div>
                            <form action="{{ route('notifications.read', $notification) }}" method="POST" class="ml-4">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Families Card -->
        <a href="{{ route('families.index') }}" class="group bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-1">{{ $familiesCount }}</h3>
            <p class="text-sm text-gray-600">Total Families</p>
        </a>

        <!-- Requests Card -->
        <a href="{{ route('family-member-requests.index') }}" class="group bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 relative">
            @if($pendingRequestsCount > 0)
                <span class="absolute top-4 right-4 bg-red-500 text-white text-xs font-bold px-2.5 py-1 rounded-full animate-pulse">{{ $pendingRequestsCount }}</span>
            @endif
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-1">{{ $pendingRequestsCount }}</h3>
            <p class="text-sm text-gray-600">Pending Requests</p>
        </a>

        <!-- Quick Actions Card -->
        @if($familiesCount === 0)
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Quick Actions</h3>
                <p class="text-sm text-gray-600 mb-4">Get started quickly</p>
                <a href="{{ route('families.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Family
                </a>
            </div>
        @endif
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('families.index') }}" class="group bg-white rounded-xl shadow-lg border border-gray-200 p-8 hover:shadow-xl hover:scale-[1.01] transition-all duration-300">
            <div class="flex items-start space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">Manage Families</h3>
                    <p class="text-gray-600 mb-4">View and manage all your family groups, members, and roles in one place.</p>
                    <span class="inline-flex items-center text-blue-600 font-semibold text-sm group-hover:translate-x-1 transition-transform">
                        View Families
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </span>
                </div>
            </div>
        </a>

        <a href="{{ route('family-member-requests.index') }}" class="group bg-white rounded-xl shadow-lg border border-gray-200 p-8 hover:shadow-xl hover:scale-[1.01] transition-all duration-300">
            <div class="flex items-start space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-purple-600 transition-colors">Family Requests</h3>
                    <p class="text-gray-600 mb-4">Review and respond to family member requests from other users.</p>
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

