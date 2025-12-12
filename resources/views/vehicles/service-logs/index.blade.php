<x-app-layout title="Service Logs">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Service Logs'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Service Logs</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">{{ $vehicle->make }} {{ $vehicle->model }}</p>
                </div>
                <a href="{{ route('families.vehicles.service-logs.create', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                    <x-button variant="primary" size="md">Add Service Log</x-button>
                </a>
            </div>

            @if($serviceLogs->count() > 0)
                <div class="space-y-3">
                    @foreach($serviceLogs as $log)
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $log->service_center_name ?? 'Service' }}</h3>
                                        <span class="text-xs px-2 py-1 rounded-full bg-[var(--color-bg-primary)] text-[var(--color-text-secondary)]">
                                            {{ ucfirst(str_replace('_', ' ', $log->service_type)) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-[var(--color-text-secondary)] mb-2">{{ $log->service_date->format('M d, Y') }} • {{ number_format($log->odometer_reading) }} km • ₹{{ number_format($log->cost, 2) }}</p>
                                    @if($log->description)
                                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $log->description }}</p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('families.vehicles.service-logs.edit', ['family' => $family->id, 'vehicle' => $vehicle->id, 'serviceLog' => $log->id]) }}" class="text-[var(--color-primary)] hover:underline text-sm">Edit</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $serviceLogs->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No service logs found.</p>
                    <a href="{{ route('families.vehicles.service-logs.create', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                        <x-button variant="primary" size="md">Add First Service Log</x-button>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

