<x-app-layout title="Vehicles">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Vehicles</h2>
                        <p class="text-sm text-[var(--color-text-secondary)]">Manage your family vehicles and maintenance.</p>
                    </div>
                    @can('create', \App\Models\Vehicle::class)
                        <a href="{{ route('families.vehicles.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Vehicle</x-button>
                        </a>
                    @endcan
                </div>

                <form method="GET" action="{{ route('families.vehicles.index', ['family' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-xl p-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Make, model, or registration..." class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Owner</label>
                        <select name="family_member_id" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All members</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" @selected(($filters['family_member_id'] ?? '') == $member->id)>
                                    {{ $member->first_name }} {{ $member->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end justify-end gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="expiring_soon" value="1" @checked(($filters['expiring_soon'] ?? '') == '1') class="rounded border-[var(--color-border-primary)]">
                            <span class="text-sm text-[var(--color-text-secondary)]">Expiring Soon (30 days)</span>
                        </label>
                        <x-button type="submit" variant="primary" size="md">Apply</x-button>
                        <a href="{{ route('families.vehicles.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)] transition-colors">Reset</a>
                    </div>
                </form>
            </div>

            @if($vehicles->count() > 0)
                <div class="mt-6 grid grid-cols-1 gap-4">
                    @foreach($vehicles as $vehicle)
                        <a href="{{ route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" class="block bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})</h3>
                                        <span class="text-xs px-2 py-1 rounded-full bg-[var(--color-bg-primary)] text-[var(--color-text-secondary)]">
                                            {{ strtoupper($vehicle->registration_number) }}
                                        </span>
                                        @if($vehicle->fuel_type)
                                            <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                                                {{ ucfirst($vehicle->fuel_type) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-4 text-xs text-[var(--color-text-secondary)] mb-2">
                                        @if($vehicle->familyMember)
                                            <span>Owner: {{ $vehicle->familyMember->first_name }} {{ $vehicle->familyMember->last_name }}</span>
                                        @endif
                                        @if($vehicle->rc_expiry_date)
                                            @php
                                                $daysUntilRc = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($vehicle->rc_expiry_date), false);
                                            @endphp
                                            <span class="{{ $daysUntilRc <= 30 ? 'text-red-600 font-semibold' : '' }}">
                                                RC: {{ $vehicle->rc_expiry_date->format('M d, Y') }}
                                                @if($daysUntilRc <= 30 && $daysUntilRc >= 0)
                                                    ({{ $daysUntilRc }} days left)
                                                @elseif($daysUntilRc < 0)
                                                    (Expired {{ abs($daysUntilRc) }} days ago)
                                                @endif
                                            </span>
                                        @endif
                                        @if($vehicle->insurance_expiry_date)
                                            @php
                                                $daysUntilInsurance = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($vehicle->insurance_expiry_date), false);
                                            @endphp
                                            <span class="{{ $daysUntilInsurance <= 30 ? 'text-red-600 font-semibold' : '' }}">
                                                Insurance: {{ $vehicle->insurance_expiry_date->format('M d, Y') }}
                                                @if($daysUntilInsurance <= 30 && $daysUntilInsurance >= 0)
                                                    ({{ $daysUntilInsurance }} days left)
                                                @elseif($daysUntilInsurance < 0)
                                                    (Expired {{ abs($daysUntilInsurance) }} days ago)
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-[var(--color-text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $vehicles->links() }}
                </div>
            @else
                <div class="mt-6 text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No vehicles found.</p>
                    @can('create', \App\Models\Vehicle::class)
                        <a href="{{ route('families.vehicles.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Your First Vehicle</x-button>
                        </a>
                    @endcan
                </div>
            @endif

            <!-- Fuel Consumption Trends Chart -->
            @if(count($fuelConsumptionData ?? []) > 0)
                <div class="mt-6 card">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Fuel Consumption Trends (Last 12 Months)</h2>
                    <div id="fuelConsumptionChart" style="min-height: 400px;"></div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
        <script src="{{ asset('js/vehicle-charts.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Fuel Consumption Data
                const fuelConsumptionData = @json($fuelConsumptionData ?? []);
                
                // Initialize charts once ApexCharts is loaded
                if (typeof ApexCharts !== 'undefined' && typeof initVehicleCharts === 'function') {
                    initVehicleCharts(fuelConsumptionData);
                } else {
                    // Wait for ApexCharts to load
                    window.addEventListener('load', function() {
                        if (typeof ApexCharts !== 'undefined' && typeof initVehicleCharts === 'function') {
                            initVehicleCharts(fuelConsumptionData);
                        } else {
                            console.error('ApexCharts or initVehicleCharts function not available');
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>

