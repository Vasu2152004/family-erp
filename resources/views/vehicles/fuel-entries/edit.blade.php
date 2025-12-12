<x-app-layout title="Edit Fuel Entry">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Fuel Entries', 'url' => route('families.vehicles.fuel-entries.index', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Edit'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Edit Fuel Entry</h2>

            <form method="POST" action="{{ route('families.vehicles.fuel-entries.update', ['family' => $family->id, 'vehicle' => $vehicle->id, 'fuelEntry' => $fuelEntry->id]) }}">
                @csrf
                @method('PATCH')
                @include('vehicles.fuel-entries._form')
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Update Fuel Entry</x-button>
                    <a href="{{ route('families.vehicles.fuel-entries.index', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

