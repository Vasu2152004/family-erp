<x-app-layout title="Add Vehicle">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => 'Add Vehicle'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Add Vehicle</h2>

            <form method="POST" action="{{ route('families.vehicles.store', ['family' => $family->id]) }}">
                @csrf
                @include('vehicles._form')
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Create Vehicle</x-button>
                    <a href="{{ route('families.vehicles.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

