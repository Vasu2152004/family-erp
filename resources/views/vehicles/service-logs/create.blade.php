<x-app-layout title="Add Service Log">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Service Logs', 'url' => route('families.vehicles.service-logs.index', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Add Service Log'],
        ]" />

        <div class="card card-contrast">
            <p class="pill mb-3 w-fit">Service</p>
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Add Service Log</h2>

            <x-form method="POST" action="{{ route('families.vehicles.service-logs.store', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                @include('vehicles.service-logs._form', ['serviceLog' => null])
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Create Service Log</x-button>
                    <a href="{{ route('families.vehicles.service-logs.index', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>




