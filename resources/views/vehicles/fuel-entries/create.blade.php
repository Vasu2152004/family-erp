<x-app-layout title="Add Fuel Entry">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Fuel Entries', 'url' => route('families.vehicles.fuel-entries.index', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Add Fuel Entry'],
        ]" />

        <div class="card card-contrast">
            <p class="pill mb-3 w-fit">Fuel log</p>
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Add Fuel Entry</h2>

            <form method="POST" action="{{ route('families.vehicles.fuel-entries.store', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                @csrf
                @include('vehicles.fuel-entries._form', ['fuelEntry' => null])
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Create Fuel Entry</x-button>
                    <a href="{{ route('families.vehicles.fuel-entries.index', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>




