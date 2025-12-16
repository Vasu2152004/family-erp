@props(['active' => ''])

<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] transform transition-transform duration-300 ease-in-out z-50 lg:translate-x-0 -translate-x-full shadow-2xl flex flex-col border-r border-[var(--color-border-primary)]">
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between h-16 px-6 border-b border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)]">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-[var(--color-primary)]/10 rounded-xl flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold text-[var(--color-text-primary)]">
                    {{ config('app.name', 'Family ERP') }}
                </h1>
                <p class="text-xs text-[var(--color-text-secondary)]">Home Management</p>
            </div>
        </div>
        <button id="sidebar-close" class="lg:hidden text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- User Profile Section -->
    <div class="px-6 py-4 border-b border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)]">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-[var(--color-primary)]/10 rounded-full flex items-center justify-center text-[var(--color-primary)] font-semibold shadow-sm">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-[var(--color-text-primary)] truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-[var(--color-text-secondary)] truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3 sidebar-scroll">
        @php
            $routeName = request()->route()?->getName();
            $user = Auth::user();
            $familyIdsFromRoles = \App\Models\FamilyUserRole::where('user_id', $user->id)->pluck('family_id');
            $familyIdsFromMembers = \App\Models\FamilyMember::where('user_id', $user->id)->pluck('family_id');
            $familyIds = $familyIdsFromRoles->merge($familyIdsFromMembers)->unique()->values();
            $accessibleFamilies = \App\Models\Family::whereIn('id', $familyIds)->orderBy('name')->get();
            
            // Get active family - ensure it's a model instance, not a string/ID
            $routeFamily = request()->route('family');
            if ($routeFamily instanceof \App\Models\Family) {
                $activeFamily = $routeFamily;
            } elseif (is_numeric($routeFamily)) {
                $activeFamily = \App\Models\Family::find($routeFamily) ?? $accessibleFamilies->first();
            } else {
                $activeFamily = $accessibleFamilies->first();
            }

            $currentRouteName = request()->route()?->getName() ?? '';
            $currentPath = request()->path();
            
            $match = function (array $routes, array $paths = []) use ($currentRouteName, $currentPath) {
                // Check route names with wildcard support
                foreach ($routes as $pattern) {
                    // Convert Laravel wildcard pattern to regex
                    $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/';
                    if (preg_match($regex, $currentRouteName)) {
                        return true;
                    }
                }
                
                // Use Laravel's routeIs() as fallback
                if (request()->routeIs($routes)) {
                    return true;
                }
                
                // Fallback to URL path matching
                foreach ($paths as $path) {
                    if (request()->is($path) || str_contains($currentPath, trim($path, '/*'))) {
                        return true;
                    }
                }
                
                return false;
            };

            $activeClasses = 'nav-active';
            $inactiveClasses = 'nav-inactive';
        @endphp
        <ul class="space-y-1">
            <li>
                @php $isActive = $match(['dashboard'], ['dashboard']); @endphp
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>
            </li>

            <li>
                @php
                    // Families should be active for family routes, but NOT for inventory or shopping-list
                    $currentRoute = request()->route()?->getName() ?? '';
                    $isActive = (
                        (str_starts_with($currentRoute, 'families.') && 
                         !str_starts_with($currentRoute, 'families.inventory.') &&
                         !str_starts_with($currentRoute, 'families.shopping-list.') &&
                         !str_starts_with($currentRoute, 'families.calendar.') &&
                         !str_starts_with($currentRoute, 'families.transactions.') &&
                         !str_starts_with($currentRoute, 'families.budgets.') &&
                         !str_starts_with($currentRoute, 'families.finance-accounts.') &&
                         !str_starts_with($currentRoute, 'families.finance-analytics.') &&
                         !str_starts_with($currentRoute, 'families.documents.') &&
                         !str_starts_with($currentRoute, 'families.document-types.') &&
                         !str_starts_with($currentRoute, 'families.health.') &&
                         !str_starts_with($currentRoute, 'families.tasks.') &&
                         !str_starts_with($currentRoute, 'families.notes.') &&
                         !str_starts_with($currentRoute, 'families.vehicles.')) ||
                        str_starts_with($currentRoute, 'family-member-requests.')
                    );
                @endphp
                <a href="{{ route('families.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="font-medium">Families</span>
                </a>
            </li>

            <li>
                @php
                    $isActive = $match(
                        [
                            'finance.*',
                            'finance-accounts.*',
                            'families.finance-accounts.*',
                            'families.transactions.*',
                            'families.budgets.*',
                            'families.finance-analytics.*',
                        ],
                        [
                            'finance*',
                            'families/*/finance-*',
                            'families/*/transactions*',
                            'families/*/budgets*',
                            'families/*/finance-analytics*',
                        ]
                    );
                @endphp
                <a href="{{ route('finance.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium">Finance</span>
                </a>
            </li>

            @if($activeFamily)
                <li>
                    @php
                        $isActive = $match(
                            [
                                'inventory.*',
                                'inventory.items.*',
                                'inventory.categories.*',
                                'families.inventory.*',
                                'families.inventory.items.*',
                                'families.inventory.categories.*',
                            ],
                            [
                                'inventory*',
                                'families/*/inventory*',
                            ]
                        );
                    @endphp
                    <a href="{{ route('families.inventory.items.index', ['family' => $activeFamily->id]) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span class="font-medium">Inventory</span>
                    </a>
                </li>

            <li>
                @php
                    $isActive = $match(
                        [
                            'calendar.*',
                            'families.calendar.*',
                        ],
                        [
                            'calendar*',
                            'families/*/calendar*',
                        ]
                    );
                @endphp
                <a href="{{ route('families.calendar.index', ['family' => $activeFamily->id]) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-12 8h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">Calendar</span>
                </a>
            </li>

                <li>
                    @php
                        $isActive = $match(
                            [
                                'families.health.*',
                            ],
                            [
                                'families/*/health*',
                            ]
                        );
                    @endphp
                    <a href="{{ route('families.health.index', ['family' => $activeFamily->id]) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6m14 0a10 10 0 11-20 0 10 10 0 0120 0z"></path>
                        </svg>
                        <span class="font-medium">Health</span>
                    </a>
                </li>

                <li>
                    @php
                        $isActive = $match(
                            [
                                'families.tasks.*',
                            ],
                            [
                                'families/*/tasks*',
                            ]
                        );
                    @endphp
                    <a href="{{ route('families.tasks.index', ['family' => $activeFamily->id]) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <span class="font-medium">Tasks</span>
                    </a>
                </li>

                <li>
                    @php
                        $isActive = $match(
                            [
                                'families.notes.*',
                            ],
                            [
                                'families/*/notes*',
                            ]
                        );
                    @endphp
                    <a href="{{ route('families.notes.index', ['family' => $activeFamily->id]) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l9-5-9-5-9 5 9 5z"></path>
                        </svg>
                        <span class="font-medium">Notes</span>
                    </a>
                </li>

                <li>
                    @php
                        $isActive = $match(
                            [
                                'families.vehicles.*',
                            ],
                            [
                                'families/*/vehicles*',
                            ]
                        );
                    @endphp
                    <a href="{{ route('families.vehicles.index', ['family' => $activeFamily->id]) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                        </svg>
                        <span class="font-medium">Vehicles</span>
                    </a>
                </li>

                <li>
                    @php
                        $isActive = $match(
                            [
                                'families.documents.*',
                            ],
                            [
                                'families/*/documents*',
                            ]
                        );
                    @endphp
                    <a href="{{ route('families.documents.index', ['family' => $activeFamily->id]) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h6m5-8V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14l4-4h8a2 2 0 002-2z"></path>
                        </svg>
                        <span class="font-medium">Documents</span>
                    </a>
                </li>

                <li>
                    @php
                        $isActive = $match(
                            [
                                'shopping-list.*',
                                'families.shopping-list.*',
                            ],
                            [
                                'shopping-list*',
                                'families/*/shopping-list*',
                            ]
                        );
                    @endphp
                    <a href="{{ route('families.shopping-list.index', ['family' => $activeFamily->id]) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span class="font-medium">Shopping List</span>
                    </a>
                </li>
            @endif

            <li>
                @php
                    $isActive = $match(
                        ['notifications.*'],
                        ['notifications*']
                    );
                @endphp
                <a href="{{ route('notifications.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? $activeClasses : $inactiveClasses }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="font-medium">Notifications</span>
                    @php
                        $unreadCount = Auth::user()->unreadNotifications()->count();
                    @endphp
                    @if($unreadCount > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">{{ $unreadCount }}</span>
                    @endif
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="px-6 py-4 border-t border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)]">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-[var(--color-text-secondary)] hover:bg-red-50 hover:text-red-600 transition-all duration-200 group">
                <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="font-medium">Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden hidden"></div>

