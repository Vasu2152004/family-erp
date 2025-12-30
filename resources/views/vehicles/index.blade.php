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
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <a href="{{ route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" class="flex-1">
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
                                </a>
                                <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                                    <a href="{{ route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors" title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                    @can('delete', $vehicle)
                                        <button type="button" onclick="openDeleteModal({{ $vehicle->id }}, '{{ addslashes($vehicle->make) }} {{ addslashes($vehicle->model) }}', '{{ $vehicle->registration_number }}')" title="Delete Vehicle">
                                            <x-button variant="danger-outline" size="sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </x-button>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
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

    <!-- Delete Vehicle Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-xl border border-[var(--color-border-primary)] max-w-md w-full p-6 animate-fade-in">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Delete Vehicle</h3>
                <button type="button" onclick="closeDeleteModal()" class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors" aria-label="Close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" id="deleteForm" class="space-y-4">
                @csrf
                @method('DELETE')
                
                <div>
                    <p class="text-[var(--color-text-secondary)] mb-4">
                        Are you sure you want to delete <strong id="vehicleName" class="text-[var(--color-text-primary)]"></strong> (<span id="vehicleReg" class="text-[var(--color-text-primary)]"></span>)?
                    </p>
                    
                    <label class="flex items-center gap-2 cursor-pointer mb-4">
                        <input type="checkbox" name="is_sold" id="is_sold" value="1" onchange="toggleSaleFields()" class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                        <span class="text-sm text-[var(--color-text-primary)]">Vehicle is sold</span>
                    </label>
                    
                    <div id="saleFields" class="hidden space-y-3">
                        <div>
                            <label for="sold_to" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Sold To <span class="text-red-600">*</span></label>
                            <input type="text" name="sold_to" id="sold_to" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Buyer name">
                        </div>
                        
                        <div>
                            <label for="sold_date" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Sold Date <span class="text-red-600">*</span></label>
                            <input type="date" name="sold_date" id="sold_date" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" value="{{ date('Y-m-d') }}">
                        </div>
                        
                        <div>
                            <label for="sold_price" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Sold Price <span class="text-red-600">*</span></label>
                            <input type="number" name="sold_price" id="sold_price" step="0.01" min="0.01" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="0.00">
                        </div>
                        
                        <div>
                            <label for="notes" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Notes</label>
                            <textarea name="notes" id="notes" rows="3" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Additional notes about the sale"></textarea>
                        </div>
                        
                        <p class="text-xs text-[var(--color-text-secondary)]">
                            An income transaction will be automatically created in your finance module.
                        </p>
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors font-medium">
                        Delete Vehicle
                    </button>
                </div>
            </form>
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

            function openDeleteModal(vehicleId, vehicleName, vehicleReg) {
                document.getElementById('deleteModal').classList.remove('hidden');
                document.getElementById('deleteModal').classList.add('flex');
                document.getElementById('vehicleName').textContent = vehicleName;
                document.getElementById('vehicleReg').textContent = vehicleReg;
                const baseUrl = '{{ route("families.vehicles.destroy", ["family" => $family->id, "vehicle" => 0]) }}';
                document.getElementById('deleteForm').action = baseUrl.replace('/0', '/' + vehicleId);
            }
            
            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
                document.getElementById('deleteModal').classList.remove('flex');
                // Reset form
                document.getElementById('deleteForm').reset();
                document.getElementById('is_sold').checked = false;
                toggleSaleFields();
            }
            
            function toggleSaleFields() {
                const isSold = document.getElementById('is_sold').checked;
                const saleFields = document.getElementById('saleFields');
                
                if (isSold) {
                    saleFields.classList.remove('hidden');
                    // Make fields required
                    document.getElementById('sold_to').required = true;
                    document.getElementById('sold_date').required = true;
                    document.getElementById('sold_price').required = true;
                } else {
                    saleFields.classList.add('hidden');
                    // Remove required
                    document.getElementById('sold_to').required = false;
                    document.getElementById('sold_date').required = false;
                    document.getElementById('sold_price').required = false;
                }
            }
            
            // Close modal on outside click
            document.getElementById('deleteModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeDeleteModal();
                }
            });
        </script>
    @endpush
</x-app-layout>

