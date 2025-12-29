<x-app-layout title="Edit Vehicle">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Edit'],
        ]" />

        <div class="card card-contrast">
            <p class="pill mb-3 w-fit">Garage</p>
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Edit Vehicle</h2>

            <x-form method="PATCH" action="{{ route('families.vehicles.update', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                @include('vehicles._form')
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Update Vehicle</x-button>
                    <a href="{{ route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>




