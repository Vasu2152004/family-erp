<x-app-layout title="Notifications">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Notifications']
        ]" />

        <!-- Header -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Notifications</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        View and manage your notifications
                    </p>
                </div>
                @if($notifications->count() > 0 && Auth::user()->unreadNotifications()->count() > 0)
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf
                        <x-button type="submit" variant="secondary" size="md">Mark All as Read</x-button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Notifications List -->
        @if($notifications->count() > 0)
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <div class="space-y-4">
                    @foreach($notifications as $notification)
                        @php
                            $isBudgetAlert = in_array($notification->type, ['budget_alert', 'budget_exceeded']);
                            $isExceeded = $notification->type === 'budget_exceeded';
                        @endphp
                        <div class="rounded-lg border p-6 {{ $isBudgetAlert ? ($isExceeded ? 'bg-red-50 border-red-300 border-l-4 border-l-red-600' : 'bg-orange-50 border-orange-300 border-l-4 border-l-orange-500') : (!$notification->read_at ? 'bg-[var(--color-bg-secondary)] border-[var(--color-border-primary)] border-l-4 border-l-[var(--color-primary)]' : 'bg-[var(--color-bg-secondary)] border-[var(--color-border-primary)]') }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        @if($isBudgetAlert)
                                            <div class="w-8 h-8 {{ $isExceeded ? 'bg-red-500' : 'bg-orange-500' }} rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                            </div>
                                        @elseif(!$notification->read_at)
                                            <span class="w-2 h-2 bg-[var(--color-primary)] rounded-full"></span>
                                        @endif
                                        <h3 class="text-lg font-semibold {{ $isBudgetAlert ? ($isExceeded ? 'text-red-800' : 'text-orange-800') : 'text-[var(--color-text-primary)]' }}">
                                            {{ $notification->title }}
                                            @if($isExceeded)
                                                <span class="ml-2 px-2 py-0.5 bg-red-600 text-white text-xs font-bold rounded">URGENT</span>
                                            @endif
                                        </h3>
                                    </div>
                                    <p class="text-sm {{ $isBudgetAlert ? ($isExceeded ? 'text-red-700' : 'text-orange-700') : 'text-[var(--color-text-secondary)]' }} mb-3">
                                        {{ $notification->message }}
                                    </p>
                                    <div class="flex items-center gap-4 text-xs {{ $isBudgetAlert ? ($isExceeded ? 'text-red-600' : 'text-orange-600') : 'text-[var(--color-text-tertiary)]' }}">
                                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                                        @if($notification->data && isset($notification->data['family_id']))
                                            @php
                                                $family = \App\Models\Family::find($notification->data['family_id']);
                                            @endphp
                                            @if($family)
                                                <span class="font-semibold">
                                                    Family: {{ $family->name }}
                                                </span>
                                                @if($isBudgetAlert && isset($notification->data['budget_id']))
                                                    <a href="{{ route('finance.budgets.index', ['family_id' => $family->id]) }}" class="underline hover:no-underline font-bold">
                                                        View Budgets →
                                                    </a>
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                @if(!$notification->read_at)
                                    <form action="{{ route('notifications.read', $notification) }}" method="POST" class="ml-4">
                                        @csrf
                                        <x-button type="submit" variant="outline" size="sm">Mark as Read</x-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
            </div>
        @else
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-12 text-center">
                <svg class="w-16 h-16 text-[var(--color-text-tertiary)] mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mb-2">No Notifications</h3>
                <p class="text-[var(--color-text-secondary)]">You're all caught up! No new notifications.</p>
            </div>
        @endif
    </div>
</x-app-layout>


