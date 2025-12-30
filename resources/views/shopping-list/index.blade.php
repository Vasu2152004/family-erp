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
                        <x-form method="POST" action="{{ route('shopping-list.auto-add-low-stock', ['family_id' => $family->id]) }}" class="inline">
                            <x-button type="submit" variant="outline" size="md">Auto-Add Low Stock</x-button>
                        </x-form>
                    @endcan
                    @if($purchasedItems->count() > 0)
                        <x-form 
                            method="DELETE" 
                            action="{{ route('shopping-list.clear-purchased', ['family_id' => $family->id]) }}" 
                            class="inline"
                            data-confirm="Are you sure you want to clear all purchased items?"
                            data-confirm-title="Clear Purchased Items"
                            data-confirm-variant="danger"
                        >
                            <x-button type="submit" variant="outline" size="md">Clear Purchased</x-button>
                        </x-form>
                    @endif
                </div>
            </div>

            <!-- Add Item Form -->
            @can('create', [\App\Models\ShoppingListItem::class, $family])
                <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-6 mb-6">
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Add Item</h2>
                    <x-form method="POST" action="{{ route('shopping-list.store', ['family_id' => $family->id]) }}" class="space-y-4">
                        <input type="hidden" name="family_id" value="{{ $family->id }}">

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-label for="name" required>Item Name</x-label>
                                <x-input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1" />
                                <x-error-message field="name" />
                            </div>

                            <div>
                                <x-label for="qty" required>Quantity</x-label>
                                <x-input type="number" name="qty" id="qty" value="{{ old('qty', 1) }}" step="0.01" min="0.01" required class="mt-1" />
                                <x-error-message field="qty" />
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
                                <x-error-message field="unit" />
                            </div>

                            <div>
                                <x-label for="inventory_item_id">From Inventory</x-label>
                                <select name="inventory_item_id" id="inventory_item_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                    <option value="">Manual Entry</option>
                                    @foreach($inventoryItems as $invItem)
                                        <option value="{{ $invItem->id }}">{{ $invItem->name }} ({{ number_format($invItem->qty, 2) }} {{ $invItem->unit }})</option>
                                    @endforeach
                                </select>
                                <x-error-message field="inventory_item_id" />
                            </div>
                        </div>

                        <div>
                            <x-label for="notes">Notes</x-label>
                            <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('notes') }}</textarea>
                            <x-error-message field="notes" />
                        </div>

                        <div>
                            <x-button type="submit" variant="primary" size="md">Add to List</x-button>
                        </div>
                    </x-form>
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
                                        <button type="button" onclick="openPurchaseModal({{ $item->id }}, '{{ addslashes($item->name) }}')" class="px-3 py-1.5 bg-[var(--color-primary)] text-white rounded-lg hover:bg-[var(--color-primary-dark)] transition-colors text-sm font-medium">
                                            Mark Purchased
                                        </button>
                                    @endcan
                                    @can('update', $item)
                                        <a href="#" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)] text-sm">Edit</a>
                                    @endcan
                                    @can('delete', $item)
                                        <x-form 
                                            method="DELETE" 
                                            action="{{ route('shopping-list.destroy', ['item' => $item->id, 'family_id' => $family->id]) }}" 
                                            class="inline"
                                            data-confirm="Are you sure you want to remove this item?"
                                            data-confirm-title="Remove Item"
                                            data-confirm-variant="danger"
                                        >
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                                        </x-form>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">
                        {{ $pendingItems->links() }}
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
                                            @if($item->amount)
                                                • Amount: ₹{{ number_format($item->amount, 2) }}
                                            @endif
                                        </p>
                                        <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                            Purchased by {{ $item->purchasedBy->name ?? 'Unknown' }} on {{ $item->purchased_at->format('M d, Y') }}
                                            @if($item->budget)
                                                • Budget: {{ $item->budget->category->name ?? 'Uncategorized' }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        @can('update', $item)
                                            <x-form method="PATCH" action="{{ route('shopping-list.mark-pending', ['item' => $item->id, 'family_id' => $family->id]) }}" class="inline">
                                                <x-button type="submit" variant="outline" size="sm">Mark Pending</x-button>
                                            </x-form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">
                        {{ $purchasedItems->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Purchase Modal -->
    <div id="purchaseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-xl border border-[var(--color-border-primary)] p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">Mark as Purchased</h3>
            
            <x-form method="PATCH" id="purchaseForm" class="space-y-4">
                <input type="hidden" name="family_id" value="{{ $family->id }}">
                
                <div>
                    <p class="text-[var(--color-text-secondary)] mb-4">
                        Item: <strong id="purchaseItemName"></strong>
                    </p>
                </div>
                
                <div>
                    <label for="amount" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Amount (₹)</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0.01" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="0.00">
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Leave empty if no transaction needed</p>
                </div>
                
                <div>
                    <label for="budget_id" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Budget (Optional)</label>
                    <select name="budget_id" id="budget_id" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">No Budget</option>
                        @foreach($budgets as $budget)
                            <option value="{{ $budget->id }}">
                                {{ $budget->category->name ?? 'Uncategorized' }}
                                @if($budget->family_member_id)
                                    (Personal)
                                @else
                                    (Family)
                                @endif
                                - ₹{{ number_format($budget->amount, 2) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Only your personal budgets and family budgets are shown</p>
                </div>
                
                <div class="flex gap-3 justify-end pt-4">
                    <button type="button" onclick="closePurchaseModal()" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-dark)] transition-colors">
                        Mark Purchased
                    </button>
                </div>
            </x-form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openPurchaseModal(itemId, itemName) {
                document.getElementById('purchaseModal').classList.remove('hidden');
                document.getElementById('purchaseModal').classList.add('flex');
                document.getElementById('purchaseItemName').textContent = itemName;
                const baseUrl = '{{ route("shopping-list.mark-purchased", ["item" => 0, "family_id" => $family->id]) }}';
                document.getElementById('purchaseForm').action = baseUrl.replace('/0/', '/' + itemId + '/');
            }
            
            function closePurchaseModal() {
                document.getElementById('purchaseModal').classList.add('hidden');
                document.getElementById('purchaseModal').classList.remove('flex');
                document.getElementById('purchaseForm').reset();
            }
            
            // Close modal on outside click
            document.getElementById('purchaseModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closePurchaseModal();
                }
            });
        </script>
    @endpush
</x-app-layout>

