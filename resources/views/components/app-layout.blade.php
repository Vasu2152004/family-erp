<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'Family ERP') }}</title>

    <!-- Performance Optimizations -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet"></noscript>

    <!-- Styles / Scripts -->
    <x-asset-loader />
</head>
<body class="min-h-screen bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Main Content Area -->
    <div class="lg:pl-64 min-h-screen">
        <!-- Top Navigation Bar -->
        <header class="sticky top-0 z-30 bg-[var(--color-bg-secondary)]/90 backdrop-blur-xl border-b border-[var(--color-border-primary)] shadow-sm">
            <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                <!-- Mobile Menu Button -->
                <button id="sidebar-toggle" class="lg:hidden p-2 rounded-xl text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <!-- Page Title -->
                <div class="flex-1 lg:flex-none">
                    <h1 class="text-xl font-bold text-[var(--color-text-primary)]">
                        {{ $title ?? 'Dashboard' }}
                    </h1>
                </div>

                <!-- User Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <a href="{{ route('notifications.index') }}" class="p-2 rounded-xl text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        @php
                            $unreadCount = Auth::user()->unreadNotifications()->count();
                        @endphp
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center animate-pulse">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-4 sm:p-6 lg:p-8 bg-[var(--color-bg-primary)] min-h-screen">
            <!-- Flash Messages -->
            @if(session('success'))
                <x-alert type="success" dismissible class="mb-6 animate-fade-in">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if(session('info'))
                <x-alert type="info" dismissible class="mb-6 animate-fade-in">
                    {{ session('info') }}
                </x-alert>
            @endif

            @if(session('error'))
                <x-alert type="error" dismissible class="mb-6 animate-fade-in">
                    {{ session('error') }}
                </x-alert>
            @endif

            {{ $slot }}
        </main>
    </div>
    
    <!-- Custom Alerts Container (dynamically populated by JavaScript) -->
    <div id="custom-alerts-container" class="fixed top-4 right-4 z-50 space-y-2 max-w-md w-full"></div>
    
    @stack('scripts')
</body>
</html>



