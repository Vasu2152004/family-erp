<x-app-layout title="Inventory Items: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Inventory']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Inventory Items</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage inventory items for {{ $family->name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('create', [\App\Models\InventoryItem::class, $family])
                        <a href="{{ route('inventory.items.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Item</x-button>
                        </a>
                    @endcan
                    <a href="{{ route('inventory.categories.index', ['family_id' => $family->id]) }}">
                        <x-button variant="outline" size="md">Categories</x-button>
                    </a>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Category-wise Distribution Chart -->
                @if(count($categoryDistribution) > 0)
                    <div class="card">
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Category-wise Distribution</h2>
                        <div id="categoryDistributionChart" style="min-height: 400px;"></div>
                    </div>
                @endif

                <!-- Stock Status Overview Chart -->
                @if(count($stockStatusOverview) > 0)
                    <div class="card">
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Stock Status Overview</h2>
                        <div id="stockStatusChart" style="min-height: 400px;"></div>
                    </div>
                @endif
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('inventory.items.index', ['family_id' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <x-label for="category_id">Category</x-label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-label for="low_stock">Filter</x-label>
                    <select name="low_stock" id="low_stock" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Items</option>
                        <option value="1" {{ request('low_stock') == '1' ? 'selected' : '' }}>Low Stock Only</option>
                    </select>
                </div>
                <div>
                    <x-label for="expiring_soon">Expiring</x-label>
                    <select name="expiring_soon" id="expiring_soon" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All Items</option>
                        <option value="1" {{ request('expiring_soon') == '1' ? 'selected' : '' }}>Expiring Soon (7 days)</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <x-button type="submit" variant="outline" size="md">Apply Filters</x-button>
                    <a href="{{ route('inventory.items.index', ['family_id' => $family->id]) }}" class="ml-2">
                        <x-button type="button" variant="outline" size="md">Clear</x-button>
                    </a>
                </div>
            </form>

            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Min Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Expiry</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-[var(--color-text-primary)]">{{ $item->name }}</span>
                                            @if($item->isLowStock())
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Low Stock</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $item->category?->name ?? 'Uncategorized' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-primary)]">
                                        {{ number_format($item->getTotalQty(), 2) }} {{ $item->unit }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ number_format($item->min_qty, 2) }} {{ $item->unit }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        @php
                                            $earliestExpiry = $item->getEarliestExpiryDate();
                                            $daysUntilExpiry = $item->daysUntilExpiry();
                                        @endphp
                                        @if($earliestExpiry)
                                            <span class="{{ $daysUntilExpiry !== null && $daysUntilExpiry <= 7 ? 'text-red-600 font-semibold' : '' }}">
                                                {{ $earliestExpiry->format('M d, Y') }}
                                                @if($daysUntilExpiry !== null && $daysUntilExpiry <= 7)
                                                    <span class="text-xs">({{ $daysUntilExpiry }} days)</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-xs text-[var(--color-text-secondary)]">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $item->location ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            @can('update', $item)
                                                <a href="{{ route('inventory.items.edit', ['item' => $item->id, 'family_id' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                                                    Edit
                                                </a>
                                            @endcan
                                            @can('delete', $item)
                                                <form action="{{ route('inventory.items.destroy', ['item' => $item->id, 'family_id' => $family->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $items->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No inventory items found.</p>
                    @can('create', [\App\Models\InventoryItem::class, $family])
                        <a href="{{ route('inventory.items.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add First Item</x-button>
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
        <script src="{{ asset('js/inventory-charts.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Category Distribution Data
                const categoryData = @json($categoryDistribution ?? []);
                // Stock Status Data
                const stockStatusData = @json($stockStatusOverview ?? []);
                
                // Initialize charts once ApexCharts is loaded
                if (typeof ApexCharts !== 'undefined' && typeof initInventoryCharts === 'function') {
                    initInventoryCharts(categoryData, stockStatusData);
                } else {
                    // Wait for ApexCharts to load
                    window.addEventListener('load', function() {
                        if (typeof ApexCharts !== 'undefined' && typeof initInventoryCharts === 'function') {
                            initInventoryCharts(categoryData, stockStatusData);
                        } else {
                            console.error('ApexCharts or initInventoryCharts function not available');
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>

