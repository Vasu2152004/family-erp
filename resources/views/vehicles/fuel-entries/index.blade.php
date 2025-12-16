<x-app-layout title="Fuel Entries">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Fuel Entries'],
        ]" />

        <div class="card card-contrast">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                <div>
                    <p class="pill mb-2 w-fit">Fuel log</p>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Fuel Entries</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">{{ $vehicle->make }} {{ $vehicle->model }}</p>
                    @if($averageMileage)
                        <p class="text-sm text-[var(--color-text-secondary)] mt-1">Average Mileage: <span class="font-semibold text-[var(--color-text-primary)]">{{ number_format($averageMileage, 2) }} km/l</span></p>
                    @endif
                </div>
                <a href="{{ route('families.vehicles.fuel-entries.create', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                    <x-button variant="primary" size="md">Add Fuel Entry</x-button>
                </a>
            </div>

            @if($fuelEntries->count() > 0)
                <div class="space-y-3">
                    @foreach($fuelEntries as $entry)
                        <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border-primary)] p-4 shadow-sm">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $entry->fill_date->format('M d, Y') }}</h3>
                                        @if($entry->calculated_mileage)
                                            <span class="badge badge-success">
                                                {{ number_format($entry->calculated_mileage, 2) }} km/l
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-[var(--color-text-secondary)] mb-2">
                                        {{ number_format($entry->odometer_reading) }} km • {{ number_format($entry->fuel_amount, 2) }}L • ₹{{ number_format($entry->cost, 2) }}
                                        @if($entry->fuel_station_name)
                                            • {{ $entry->fuel_station_name }}
                                        @endif
                                    </p>
                                    @if($entry->notes)
                                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $entry->notes }}</p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('families.vehicles.fuel-entries.edit', ['family' => $family->id, 'vehicle' => $vehicle->id, 'fuelEntry' => $entry->id]) }}" class="text-[var(--color-primary)] hover:underline text-sm">Edit</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $fuelEntries->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No fuel entries found.</p>
                    <a href="{{ route('families.vehicles.fuel-entries.create', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                        <x-button variant="primary" size="md">Add First Fuel Entry</x-button>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>




