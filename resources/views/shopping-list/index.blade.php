<x-app-layout title="Shopping List: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Shopping List']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Shopping List</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage shopping list for {{ $family->name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('create', [\App\Models\ShoppingListItem::class, $family])
                        <form action="{{ route('shopping-list.auto-add-low-stock', ['family_id' => $family->id]) }}" method="POST" class="inline">
                            @csrf
                            <x-button type="submit" variant="outline" size="md">Auto-Add Low Stock</x-button>
                        </form>
                    @endcan
                    @if($purchasedItems->count() > 0)
                        <form action="{{ route('shopping-list.clear-purchased', ['family_id' => $family->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to clear all purchased items?');">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="outline" size="md">Clear Purchased</x-button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Add Item Form -->
            @can('create', [\App\Models\ShoppingListItem::class, $family])
                <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-6 mb-6">
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Add Item</h2>
                    <form method="POST" action="{{ route('shopping-list.store', ['family_id' => $family->id]) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="family_id" value="{{ $family->id }}">

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-label for="name" required>Item Name</x-label>
                                <x-input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1" />
                                @error('name')
                                    <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-label for="qty" required>Quantity</x-label>
                                <x-input type="number" name="qty" id="qty" value="{{ old('qty', 1) }}" step="0.01" min="0.01" required class="mt-1" />
                                @error('qty')
                                    <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-label for="unit" required>Unit</x-label>
                                <select name="unit" id="unit" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                    <option value="piece" {{ old('unit', 'piece') == 'piece' ? 'selected' : '' }}>Piece</option>
                                    <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                                    <option value="liter" {{ old('unit') == 'liter' ? 'selected' : '' }}>Liter</option>
                                    <option value="gram" {{ old('unit') == 'gram' ? 'selected' : '' }}>Gram</option>
                                    <option value="ml" {{ old('unit') == 'ml' ? 'selected' : '' }}>Milliliter (ml)</option>
                                    <option value="pack" {{ old('unit') == 'pack' ? 'selected' : '' }}>Pack</option>
                                    <option value="box" {{ old('unit') == 'box' ? 'selected' : '' }}>Box</option>
                                    <option value="bottle" {{ old('unit') == 'bottle' ? 'selected' : '' }}>Bottle</option>
                                    <option value="other" {{ old('unit') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('unit')
                                    <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <x-label for="inventory_item_id">From Inventory</x-label>
                                <select name="inventory_item_id" id="inventory_item_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                    <option value="">Manual Entry</option>
                                    @foreach($inventoryItems as $invItem)
                                        <option value="{{ $invItem->id }}">{{ $invItem->name }} ({{ number_format($invItem->qty, 2) }} {{ $invItem->unit }})</option>
                                    @endforeach
                                </select>
                                @error('inventory_item_id')
                                    <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <x-label for="notes">Notes</x-label>
                            <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-button type="submit" variant="primary" size="md">Add to List</x-button>
                        </div>
                    </form>
                </div>
            @endcan

            <!-- Pending Items -->
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4">Pending Items ({{ $pendingItems->count() }})</h2>
                @if($pendingItems->count() > 0)
                    <div class="space-y-3">
                        @foreach($pendingItems as $item)
                            <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4 flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-lg font-medium text-[var(--color-text-primary)]">{{ $item->name }}</h3>
                                        @if($item->is_auto_added)
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Auto-Added</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-[var(--color-text-secondary)] mt-1">
                                        Quantity: {{ number_format($item->qty, 2) }} {{ $item->unit }}
                                        @if($item->notes)
                                            • {{ $item->notes }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                        Added by {{ $item->addedBy->name }} on {{ $item->created_at->format('M d, Y') }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    @can('markPurchased', $item)
                                        <form action="{{ route('shopping-list.mark-purchased', ['item' => $item->id, 'family_id' => $family->id]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <x-button type="submit" variant="primary" size="sm">Mark Purchased</x-button>
                                        </form>
                                    @endcan
                                    @can('update', $item)
                                        <a href="#" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)] text-sm">Edit</a>
                                    @endcan
                                    @can('delete', $item)
                                        <form action="{{ route('shopping-list.destroy', ['item' => $item->id, 'family_id' => $family->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)]">
                        <p class="text-[var(--color-text-secondary)]">No pending items. Add items to get started!</p>
                    </div>
                @endif
            </div>

            <!-- Purchased Items -->
            @if($purchasedItems->count() > 0)
                <div>
                    <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4">Recently Purchased ({{ $purchasedItems->count() }})</h2>
                    <div class="space-y-3">
                        @foreach($purchasedItems as $item)
                            <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4 opacity-75">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-medium text-[var(--color-text-primary)] line-through">{{ $item->name }}</h3>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Purchased</span>
                                        </div>
                                        <p class="text-sm text-[var(--color-text-secondary)] mt-1">
                                            Quantity: {{ number_format($item->qty, 2) }} {{ $item->unit }}
                                        </p>
                                        <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                            Purchased by {{ $item->purchasedBy->name ?? 'Unknown' }} on {{ $item->purchased_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        @can('update', $item)
                                            <form action="{{ route('shopping-list.mark-pending', ['item' => $item->id, 'family_id' => $family->id]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <x-button type="submit" variant="outline" size="sm">Mark Pending</x-button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

