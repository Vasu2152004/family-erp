<x-app-layout title="Vehicle Details">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Vehicles', 'url' => route('families.vehicles.index', ['family' => $family->id])],
            ['label' => $vehicle->make . ' ' . $vehicle->model],
        ]" />

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})</h2>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ strtoupper($vehicle->registration_number) }}</p>
                </div>
                <div class="flex gap-2">
                    @can('update', $vehicle)
                        <a href="{{ route('families.vehicles.edit', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}">
                            <x-button variant="outline" size="md">Edit</x-button>
                        </a>
                    @endcan
                    @can('delete', $vehicle)
                        <button type="button" onclick="openDeleteModal()" class="px-4 py-2 rounded-lg border border-red-600 text-red-600 hover:bg-red-50 transition-colors">
                            Delete
                        </button>
                    @endcan
                    <a href="{{ route('families.vehicles.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Back</x-button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="text-sm text-[var(--color-text-secondary)]">Owner/Driver</label>
                    <p class="text-[var(--color-text-primary)] font-medium">
                        {{ $vehicle->familyMember ? $vehicle->familyMember->first_name . ' ' . $vehicle->familyMember->last_name : 'Unassigned' }}
                    </p>
                </div>
                <div>
                    <label class="text-sm text-[var(--color-text-secondary)]">Fuel Type</label>
                    <p class="text-[var(--color-text-primary)] font-medium">{{ ucfirst($vehicle->fuel_type) }}</p>
                </div>
                @if($vehicle->color)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Color</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $vehicle->color }}</p>
                    </div>
                @endif
                <div>
                    <label class="text-sm text-[var(--color-text-secondary)]">Average Mileage</label>
                    @if($averageMileage)
                        <p class="text-[var(--color-text-primary)] font-medium">{{ number_format($averageMileage, 2) }} km/l</p>
                    @else
                        @php
                            $totalEntries = $vehicle->fuelEntries()->count();
                            $entriesWithMileage = $vehicle->fuelEntries()->whereNotNull('calculated_mileage')->count();
                            $entries = $vehicle->fuelEntries()->orderBy('fill_date', 'asc')->get();
                            $hasDecreasingReadings = false;
                            if ($entries->count() > 1) {
                                $prevReading = null;
                                foreach ($entries as $entry) {
                                    if ($prevReading !== null && $entry->odometer_reading < $prevReading) {
                                        $hasDecreasingReadings = true;
                                        break;
                                    }
                                    $prevReading = $entry->odometer_reading;
                                }
                            }
                        @endphp
                        @if($totalEntries < 2)
                            <p class="text-[var(--color-text-secondary)] text-sm">Need at least 2 fuel entries to calculate mileage</p>
                        @elseif($hasDecreasingReadings)
                            <div class="text-sm">
                                <p class="text-red-600 font-medium mb-1">⚠️ Odometer readings are decreasing</p>
                                <p class="text-[var(--color-text-secondary)] text-xs">Later dates must have higher odometer readings. Please check your fuel entries.</p>
                            </div>
                        @elseif($entriesWithMileage == 0)
                            <p class="text-[var(--color-text-secondary)] text-sm">Unable to calculate mileage. Check that odometer readings increase over time.</p>
                        @else
                            <p class="text-[var(--color-text-secondary)] text-sm">Not enough valid data</p>
                        @endif
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                @if($vehicle->rc_expiry_date)
                    <div class="p-4 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)]">
                        <label class="text-sm text-[var(--color-text-secondary)]">RC Expiry</label>
                        <p class="text-[var(--color-text-primary)] font-semibold">{{ $vehicle->rc_expiry_date->format('M d, Y') }}</p>
                        @php
                            $daysUntil = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($vehicle->rc_expiry_date), false);
                        @endphp
                        @if($daysUntil <= 30)
                            <p class="text-xs text-red-600 mt-1">{{ $daysUntil >= 0 ? $daysUntil . ' days left' : 'Expired ' . abs($daysUntil) . ' days ago' }}</p>
                        @endif
                    </div>
                @endif
                @if($vehicle->insurance_expiry_date)
                    <div class="p-4 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)]">
                        <label class="text-sm text-[var(--color-text-secondary)]">Insurance Expiry</label>
                        <p class="text-[var(--color-text-primary)] font-semibold">{{ $vehicle->insurance_expiry_date->format('M d, Y') }}</p>
                        @php
                            $daysUntil = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($vehicle->insurance_expiry_date), false);
                        @endphp
                        @if($daysUntil <= 30)
                            <p class="text-xs text-red-600 mt-1">{{ $daysUntil >= 0 ? $daysUntil . ' days left' : 'Expired ' . abs($daysUntil) . ' days ago' }}</p>
                        @endif
                    </div>
                @endif
                @if($vehicle->puc_expiry_date)
                    <div class="p-4 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)]">
                        <label class="text-sm text-[var(--color-text-secondary)]">PUC Expiry</label>
                        <p class="text-[var(--color-text-primary)] font-semibold">{{ $vehicle->puc_expiry_date->format('M d, Y') }}</p>
                        @php
                            $daysUntil = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($vehicle->puc_expiry_date), false);
                        @endphp
                        @if($daysUntil <= 30)
                            <p class="text-xs text-red-600 mt-1">{{ $daysUntil >= 0 ? $daysUntil . ' days left' : 'Expired ' . abs($daysUntil) . ' days ago' }}</p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="border-t border-[var(--color-border-primary)] pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Service Logs</h3>
                    <a href="{{ route('families.vehicles.service-logs.index', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" class="text-sm text-[var(--color-primary)] hover:underline">View All</a>
                </div>
                @if($vehicle->serviceLogs->count() > 0)
                    <div class="space-y-2">
                        @foreach($vehicle->serviceLogs as $log)
                            <div class="p-3 bg-[var(--color-bg-secondary)] rounded border border-[var(--color-border-primary)]">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-[var(--color-text-primary)]">{{ $log->service_center_name ?? 'Service' }}</p>
                                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $log->service_date->format('M d, Y') }} • {{ number_format($log->odometer_reading) }} km • ₹{{ number_format($log->cost, 2) }}</p>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full bg-[var(--color-bg-primary)] text-[var(--color-text-secondary)]">
                                        {{ ucfirst(str_replace('_', ' ', $log->service_type)) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-[var(--color-text-secondary)] text-center py-4">No service logs yet.</p>
                    <a href="{{ route('families.vehicles.service-logs.create', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" class="block text-center text-[var(--color-primary)] hover:underline">Add Service Log</a>
                @endif
            </div>

            <div class="border-t border-[var(--color-border-primary)] pt-6 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Fuel Entries</h3>
                    <a href="{{ route('families.vehicles.fuel-entries.index', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" class="text-sm text-[var(--color-primary)] hover:underline">View All</a>
                </div>
                @if($vehicle->fuelEntries->count() > 0)
                    <div class="space-y-2">
                        @foreach($vehicle->fuelEntries as $entry)
                            <div class="p-3 bg-[var(--color-bg-secondary)] rounded border border-[var(--color-border-primary)]">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-[var(--color-text-primary)]">{{ $entry->fill_date->format('M d, Y') }}</p>
                                        <p class="text-sm text-[var(--color-text-secondary)]">{{ number_format($entry->odometer_reading) }} km • {{ number_format($entry->fuel_amount, 2) }}L • ₹{{ number_format($entry->cost, 2) }}</p>
                                    </div>
                                    @if($entry->calculated_mileage)
                                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                                            {{ number_format($entry->calculated_mileage, 2) }} km/l
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-[var(--color-text-secondary)] text-center py-4">No fuel entries yet.</p>
                    <a href="{{ route('families.vehicles.fuel-entries.create', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" class="block text-center text-[var(--color-primary)] hover:underline">Add Fuel Entry</a>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Vehicle Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-xl border border-[var(--color-border-primary)] p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">Delete Vehicle</h3>
            
            <form method="POST" action="{{ route('families.vehicles.destroy', ['family' => $family->id, 'vehicle' => $vehicle->id]) }}" id="deleteForm">
                @csrf
                @method('DELETE')
                
                <div class="mb-4">
                    <p class="text-[var(--color-text-secondary)] mb-4">
                        Are you sure you want to delete <strong>{{ $vehicle->make }} {{ $vehicle->model }}</strong> ({{ $vehicle->registration_number }})?
                    </p>
                    
                    <label class="flex items-center gap-2 cursor-pointer mb-4">
                        <input type="checkbox" name="is_sold" id="is_sold" value="1" onchange="toggleSaleFields()" class="rounded border-[var(--color-border-primary)]">
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
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                        Delete Vehicle
                    </button>
                </div>
            </x-form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
                document.getElementById('deleteModal').classList.add('flex');
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

