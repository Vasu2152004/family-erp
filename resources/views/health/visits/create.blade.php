<x-app-layout title="Log Doctor Visit">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', $family)],
            ['label' => 'Doctor Visits', 'url' => route('families.health.visits.index', $family)],
            ['label' => 'Log'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-[var(--color-text-primary)]">Log Visit</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">Keep track of doctor consultations and outcomes.</p>
                </div>
                <a href="{{ route('families.health.visits.index', $family) }}" class="text-sm text-[var(--color-primary)] hover:underline">Back</a>
            </div>

            <form method="POST" action="{{ route('families.health.visits.store', $family) }}" class="space-y-6">
                @csrf
                @include('health.visits._form')
                <div class="flex justify-end gap-3">
                    <a href="{{ route('families.health.visits.index', $family) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)]">Cancel</a>
                    <x-button type="submit" variant="primary" size="md">Save Visit</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

