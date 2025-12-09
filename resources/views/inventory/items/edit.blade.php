<x-app-layout title="Edit Inventory Item: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Inventory', 'url' => route('inventory.items.index', ['family_id' => $family->id])],
            ['label' => 'Edit Item']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Edit Inventory Item</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Update item details
                </p>
            </div>

            <form method="POST" action="{{ route('inventory.items.update', ['item' => $item->id, 'family_id' => $family->id]) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                <div>
                    <x-label for="name" required>Item Name</x-label>
                    <x-input type="text" name="name" id="name" value="{{ old('name', $item->name) }}" required class="mt-1" />
                    @error('name')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="category_id">Category</x-label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">Select category...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="qty" required>Current Quantity</x-label>
                        <x-input type="number" name="qty" id="qty" value="{{ old('qty', $item->qty) }}" step="0.01" min="0" required class="mt-1" />
                        @error('qty')
                            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-label for="min_qty" required>Minimum Quantity</x-label>
                        <x-input type="number" name="min_qty" id="min_qty" value="{{ old('min_qty', $item->min_qty) }}" step="0.01" min="0" required class="mt-1" />
                        @error('min_qty')
                            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="unit" required>Unit</x-label>
                        <select name="unit" id="unit" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="piece" {{ old('unit', $item->unit) == 'piece' ? 'selected' : '' }}>Piece</option>
                            <option value="kg" {{ old('unit', $item->unit) == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                            <option value="liter" {{ old('unit', $item->unit) == 'liter' ? 'selected' : '' }}>Liter</option>
                            <option value="gram" {{ old('unit', $item->unit) == 'gram' ? 'selected' : '' }}>Gram</option>
                            <option value="ml" {{ old('unit', $item->unit) == 'ml' ? 'selected' : '' }}>Milliliter (ml)</option>
                            <option value="pack" {{ old('unit', $item->unit) == 'pack' ? 'selected' : '' }}>Pack</option>
                            <option value="box" {{ old('unit', $item->unit) == 'box' ? 'selected' : '' }}>Box</option>
                            <option value="bottle" {{ old('unit', $item->unit) == 'bottle' ? 'selected' : '' }}>Bottle</option>
                            <option value="other" {{ old('unit', $item->unit) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('unit')
                            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-label for="expiry_date">Expiry Date</x-label>
                        <x-input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date', $item->expiry_date?->format('Y-m-d')) }}" class="mt-1" />
                        @error('expiry_date')
                            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <x-label for="location">Location</x-label>
                    <x-input type="text" name="location" id="location" value="{{ old('location', $item->location) }}" placeholder="e.g., Kitchen, Pantry" class="mt-1" />
                    @error('location')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="notes">Notes</x-label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('notes', $item->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4">
                    <x-button type="submit" variant="primary" size="md">Update Item</x-button>
                    <a href="{{ route('inventory.items.index', ['family_id' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">Cancel</x-button>
                    </a>
                </div>
            </form>
        </div>

        <!-- Batches (Lots) -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-[var(--color-text-primary)]">Batches / Lots</h2>
                    <p class="mt-1 text-sm text-[var(--color-text-secondary)]">
                        Track multiple expiry dates for the same item. Total quantity (base + batches): <strong>{{ number_format($item->getTotalQty(), 2) }} {{ $item->unit }}</strong>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <h3 class="text-lg font-medium text-[var(--color-text-primary)]">Existing Batches</h3>
                    @if($item->batches->count() > 0)
                        <div class="space-y-3">
                            @foreach($item->batches as $batch)
                                <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-[var(--color-text-primary)]">
                                                {{ number_format($batch->qty, 2) }} {{ $batch->unit }}
                                            </p>
                                            <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                                Expiry: {{ $batch->expiry_date ? $batch->expiry_date->format('M d, Y') : 'N/A' }}
                                            </p>
                                            @if($batch->notes)
                                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">{{ $batch->notes }}</p>
                                            @endif
                                            <p class="text-[10px] text-[var(--color-text-secondary)] mt-1">
                                                Added {{ $batch->created_at->diffForHumans() }} by {{ $batch->addedBy?->name ?? 'Unknown' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-[var(--color-text-secondary)]">No batches added yet.</p>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-medium text-[var(--color-text-primary)] mb-3">Add Batch</h3>
                    <form method="POST" action="{{ route('inventory.items.store-batch', ['item' => $item->id, 'family_id' => $family->id]) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="family_id" value="{{ $family->id }}">

                        <div>
                            <x-label for="batch_qty" required>Quantity</x-label>
                            <x-input type="number" name="qty" id="batch_qty" step="0.01" min="0.01" required class="mt-1" />
                            @error('qty')
                                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-label for="batch_unit" required>Unit</x-label>
                            <select name="unit" id="batch_unit" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                <option value="piece" {{ old('unit', $item->unit) == 'piece' ? 'selected' : '' }}>Piece</option>
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
                            <x-label for="batch_expiry">Expiry Date</x-label>
                            <x-input type="date" name="expiry_date" id="batch_expiry" value="{{ old('expiry_date') }}" class="mt-1" />
                            @error('expiry_date')
                                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-label for="batch_notes">Notes</x-label>
                            <textarea name="notes" id="batch_notes" rows="2" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-4">
                            <x-button type="submit" variant="primary" size="md">Add Batch</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

