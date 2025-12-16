<x-app-layout title="Add Vehicle">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => 'Add Vehicle'],
        ]" />

        <div class="card card-contrast">
            <p class="pill mb-3 w-fit">Garage</p>
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Add Vehicle</h2>

            <form method="POST" action="{{ route('families.vehicles.store', ['family' => $family->id]) }}">
                @csrf
                @include('vehicles._form')
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Create Vehicle</x-button>
                    <a href="{{ route('families.vehicles.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>




