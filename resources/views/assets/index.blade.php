<x-app-layout title="Assets: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Assets']
        ]" />

        <!-- Header -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Assets</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage family assets for {{ $family->name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('create', [\App\Models\Asset::class, $family])
                        <a href="{{ route('assets.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Asset</x-button>
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Filters -->
            <x-form method="GET" action="{{ route('assets.index', ['family_id' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <x-label for="asset_type">Asset Type</x-label>
                    <select name="asset_type" id="asset_type" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Types</option>
                        <option value="PROPERTY" {{ request('asset_type') == 'PROPERTY' ? 'selected' : '' }}>Property</option>
                        <option value="GOLD" {{ request('asset_type') == 'GOLD' ? 'selected' : '' }}>Gold</option>
                        <option value="JEWELRY" {{ request('asset_type') == 'JEWELRY' ? 'selected' : '' }}>Jewelry</option>
                        <option value="LAND" {{ request('asset_type') == 'LAND' ? 'selected' : '' }}>Land</option>
                    </select>
                </div>
                <div>
                    <x-label for="family_member_id">Owner</x-label>
                    <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Members</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" {{ request('family_member_id') == $member->id ? 'selected' : '' }}>
                                {{ $member->first_name }} {{ $member->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <x-button type="submit" variant="outline" size="md">Apply Filters</x-button>
                    <a href="{{ route('assets.index', ['family_id' => $family->id]) }}" class="ml-2">
                        <x-button type="button" variant="outline" size="md">Clear</x-button>
                    </a>
                </div>
            </x-form>
        </div>

        <!-- Assets List -->
        @if($assets->count() > 0)
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Owner</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Created By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Purchase Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Current Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($assets as $asset)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-[var(--color-text-primary)]">{{ $asset->name }}</span>
                                            @if($asset->is_locked)
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Locked</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        @if($asset->is_locked)
                                            <span class="text-[var(--color-text-secondary)]">Locked</span>
                                        @else
                                            {{ str_replace('_', ' ', $asset->asset_type) }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $asset->owner_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $asset->createdBy?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-primary)]">
                                        @if($asset->is_locked)
                                            <span class="text-[var(--color-text-secondary)]">Locked</span>
                                        @else
                                            {{ $asset->purchase_value ? '₹' . number_format($asset->purchase_value, 2) : 'N/A' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-primary)]">
                                        @if($asset->is_locked)
                                            <span class="text-[var(--color-text-secondary)]">Locked</span>
                                        @else
                                            {{ $asset->current_value ? '₹' . number_format($asset->current_value, 2) : 'N/A' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            @can('view', $asset)
                                                <a href="{{ route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id]) }}">
                                                    <x-button variant="outline" size="sm">View</x-button>
                                                </a>
                                            @endcan
                                            @can('update', $asset)
                                                <a href="{{ route('assets.edit', ['asset' => $asset->id, 'family_id' => $family->id]) }}">
                                                    <x-button variant="outline" size="sm">Edit</x-button>
                                                </a>
                                            @endcan
                                            @can('delete', $asset)
                                                <x-form 
                                                    method="DELETE" 
                                                    action="{{ route('assets.destroy', ['asset' => $asset->id, 'family_id' => $family->id]) }}" 
                                                    class="inline"
                                                    data-confirm="Delete this asset?"
                                                    data-confirm-title="Delete Asset"
                                                    data-confirm-variant="danger"
                                                >
                                                    <x-button type="submit" variant="danger-outline" size="sm">Delete</x-button>
                                                </x-form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $assets->links() }}
                </div>
            </div>
        @else
            <div class="card">
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No assets found.</p>
                    @can('create', [\App\Models\Asset::class, $family])
                        <a href="{{ route('assets.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add First Asset</x-button>
                        </a>
                    @endcan
                </div>
            </div>
        @endif

        <!-- Charts Section - Moved to bottom after the list -->
        @if($assets->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Asset Type Distribution Chart (Donut) -->
                <div class="card">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Asset Type Distribution</h2>
                    @if(count($typeDistributionData) > 0)
                        <div id="assetTypeDistributionChart" style="min-height: 400px;"></div>
                    @else
                        <div class="text-center py-12 text-[var(--color-text-secondary)]">
                            <p>No visible assets available for chart visualization.</p>
                            <p class="text-sm mt-2">Locked assets are excluded from charts.</p>
                        </div>
                    @endif
                </div>

                <!-- Owner-wise Distribution Chart (Donut) -->
                <div class="card">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Owner-wise Distribution</h2>
                    @if(count($ownerDistributionData) > 0)
                        <div id="assetOwnerDistributionChart" style="min-height: 400px;"></div>
                    @else
                        <div class="text-center py-12 text-[var(--color-text-secondary)]">
                            <p>No visible assets available for chart visualization.</p>
                            <p class="text-sm mt-2">Locked assets are excluded from charts.</p>
                        </div>
                    @endif
                </div>

            </div>
        @endif
    </div>

    @if($assets->count() > 0 && (count($typeDistributionData ?? []) > 0 || count($ownerDistributionData ?? []) > 0))
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
            <script src="{{ asset('js/asset-charts.js') }}"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Asset Type Distribution Data
                    const typeDistributionData = @json($typeDistributionData ?? []);
                    // Owner-wise Distribution Data
                    const ownerDistributionData = @json($ownerDistributionData ?? []);
                    
                    // Initialize charts once ApexCharts is loaded
                    if (typeof ApexCharts !== 'undefined' && typeof initAssetCharts === 'function') {
                        initAssetCharts(typeDistributionData, [], ownerDistributionData, []);
                    } else {
                        // Wait for ApexCharts to load
                        window.addEventListener('load', function() {
                            if (typeof ApexCharts !== 'undefined' && typeof initAssetCharts === 'function') {
                                initAssetCharts(typeDistributionData, [], ownerDistributionData, []);
                            } else {
                                console.error('ApexCharts or initAssetCharts function not available');
                            }
                        });
                    }
                });
            </script>
        @endpush
    @endif
</x-app-layout>

