<x-app-layout title="Dashboard">
    <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">
                Welcome, {{ $user->name }}!
            </h1>
            <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                You are successfully logged in to {{ config('app.name', 'Family ERP') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <a href="{{ route('families.index') }}" class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-6 hover:shadow-md transition-shadow">
                <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">Families</h3>
                <p class="text-sm text-[var(--color-text-secondary)]">Manage your family groups and members</p>
            </a>
            <a href="{{ route('family-member-requests.index') }}" class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-6 hover:shadow-md transition-shadow">
                <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">Requests</h3>
                <p class="text-sm text-[var(--color-text-secondary)]">View and manage family member requests</p>
            </a>
        </div>
    </div>
</x-app-layout>

