<x-app-layout title="Service Logs">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])],
            ['label' => 'Service Logs'],
        ]" />

        <div class="card card-contrast">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                <div>
                    <p class="pill mb-2 w-fit">Service</p>
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
                        <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border-primary)] p-4 shadow-sm">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $log->service_center_name ?? 'Service' }}</h3>
                                        <span class="badge bg-[var(--color-surface-alt)] text-[var(--color-text-secondary)] border-[var(--color-border-primary)]">
                                            {{ ucfirst(str_replace('_', ' ', $log->service_type)) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-[var(--color-text-secondary)] mb-2">{{ $log->service_date->format('M d, Y') }} • {{ number_format($log->odometer_reading) }} km • ₹{{ number_format($log->cost, 2) }}</p>
                                    @if($log->description)
                                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $log->description }}</p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('families.vehicles.service-logs.edit', ['family' => $family->id, 'vehicle' => $vehicle->id, 'serviceLog' => $log->id]) }}">
                                        <x-button variant="outline" size="sm">Edit</x-button>
                                    </a>
                                    <x-form 
                                        method="POST" 
                                        action="{{ route('families.vehicles.service-logs.destroy', ['family' => $family->id, 'vehicle' => $vehicle->id, 'serviceLog' => $log->id]) }}" 
                                        class="inline"
                                        data-confirm="Are you sure you want to delete this service log?"
                                        data-confirm-title="Delete Service Log"
                                        data-confirm-variant="danger"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger-outline" size="sm">Delete</x-button>
                                    </x-form>
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




