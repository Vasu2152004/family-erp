<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Authentication' }} - {{ config('app.name', 'Family ERP') }}</title>

    <!-- Performance Optimizations -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet"></noscript>

    <!-- Styles / Scripts -->
    <x-asset-loader />
</head>
<body class="min-h-screen bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-72 h-72 bg-gradient-to-br from-blue-200/40 to-indigo-200/30 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-36 -left-28 w-72 h-72 bg-gradient-to-br from-sky-200/35 to-teal-200/25 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-gradient-to-br from-indigo-100/35 to-blue-100/20 rounded-full blur-3xl"></div>
    </div>

    <!-- Grid Pattern Overlay -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmOWZhZmIiIGZpbGwtb3BhY2l0eT0iMC40Ij48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSIxLjUiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-30"></div>

    <div class="max-w-md w-full space-y-8 relative z-10">
        <!-- Logo and Title -->
        <div class="text-center transform transition-all duration-300 hover:scale-[1.01]">
            <div class="inline-flex items-center justify-center w-18 h-18 bg-[var(--color-primary)]/10 text-[var(--color-primary)] rounded-2xl shadow-md mb-4">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-bold text-[var(--color-text-primary)]">
                {{ config('app.name', 'Family ERP') }}
            </h1>
            @if(isset($subtitle))
                <p class="mt-3 text-sm text-[var(--color-text-secondary)] font-medium">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        <!-- Card with Glassmorphism Effect -->
        <div class="bg-[var(--color-bg-secondary)] rounded-2xl shadow-lg border border-[var(--color-border-primary)] p-8">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <div class="text-center">
                <div class="bg-[var(--color-bg-secondary)] rounded-xl px-6 py-4 border border-[var(--color-border-primary)] shadow-sm">
                    {{ $footer }}
                </div>
            </div>
        @endif
    </div>
</body>
</html>

