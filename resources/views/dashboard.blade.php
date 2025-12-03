<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dashboard - {{ config('app.name', 'Family ERP') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /* Fallback styles - Vite assets not built. Run 'npm run build' or 'npm run dev' */
            body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        </style>
    @endif
</head>
<body class="min-h-screen bg-[var(--color-bg-secondary)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">
                        Welcome, {{ $user->name }}!
                    </h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        You are successfully logged in to {{ config('app.name', 'Family ERP') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="outline" size="md">
                        Logout
                    </x-button>
                </form>
            </div>

            <div class="mt-8 p-6 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)]">
                <p class="text-sm text-[var(--color-text-secondary)]">
                    This is a placeholder dashboard. You can now build your home ERP features here.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

