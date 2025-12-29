<x-app-layout title="Edit Service Log">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Service Logs', 'url' => route('families.vehicles.service-logs.index', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Edit'],
        ]" />

        <div class="card card-contrast">
            <p class="pill mb-3 w-fit">Service</p>
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Edit Service Log</h2>

            <x-form method="PATCH" action="{{ route('families.vehicles.service-logs.update', ['family' => $family->id, 'vehicle' => $vehicle->id, 'serviceLog' => $serviceLog->id]) }}">
                @include('vehicles.service-logs._form')
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Update Service Log</x-button>
                    <a href="{{ route('families.vehicles.service-logs.index', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>




