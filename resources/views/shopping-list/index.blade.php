<x-app-layout title="Shopping List: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Shopping List']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            @if(session('success'))
                <x-alert type="success" dismissible class="mb-6 animate-fade-in">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if(session('error'))
                <x-alert type="error" dismissible class="mb-6 animate-fade-in">
                    {{ session('error') }}
                </x-alert>
            @endif

            @if($errors->any())
                <div class="mb-6 space-y-2">
                    @foreach($errors->all() as $error)
                        <x-alert type="error" dismissible class="animate-fade-in">
                            {{ $error }}
                        </x-alert>
                    @endforeach
                </div>
            @endif

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
                                        <option value="{{ $invItem->id }}" data-name="{{ $invItem->name }}" data-unit="{{ $invItem->unit }}">{{ $invItem->name }} ({{ number_format($invItem->qty, 2) }} {{ $invItem->unit }})</option>
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
                                        <button type="button" onclick="openPurchaseModal({{ $item->id }}, '{{ addslashes($item->name) }}')" class="inline-flex items-center justify-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed bg-[var(--color-primary)] text-white border border-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] hover:border-[var(--color-primary-dark)] focus:ring-[var(--color-primary)] shadow-sm">
                                            Mark Purchased
                                        </button>
                                    @endcan
                                    @can('update', $item)
                                        <a href="#">
                                            <x-button variant="outline" size="sm">Edit</x-button>
                                        </a>
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
                                            <x-button type="submit" variant="danger-outline" size="sm">Remove</x-button>
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
            
            <form method="POST" id="purchaseForm" class="space-y-4" data-validate="false" novalidate>
                @csrf
                @method('PATCH')
                <input type="hidden" name="family_id" id="purchase_family_id" value="{{ $family->id }}">
                
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
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Handle inventory item selection
            document.addEventListener('DOMContentLoaded', function() {
                const inventorySelect = document.getElementById('inventory_item_id');
                const nameInput = document.getElementById('name');
                const unitSelect = document.getElementById('unit');
                
                if (inventorySelect && nameInput && unitSelect) {
                    inventorySelect.addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        
                        if (selectedOption.value) {
                            // Inventory item selected - auto-fill and make readonly
                            const itemName = selectedOption.getAttribute('data-name');
                            const itemUnit = selectedOption.getAttribute('data-unit');
                            
                            if (itemName) {
                                nameInput.value = itemName;
                                nameInput.readOnly = true;
                                nameInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                                // Remove required attribute since value is set
                                nameInput.removeAttribute('required');
                            }
                            
                            if (itemUnit) {
                                // Set unit if it matches one of the options
                                const unitOption = Array.from(unitSelect.options).find(opt => opt.value === itemUnit);
                                if (unitOption) {
                                    unitSelect.value = itemUnit;
                                }
                            }
                        } else {
                            // Manual entry - enable name field
                            nameInput.readOnly = false;
                            nameInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                            // Re-add required attribute for manual entry
                            nameInput.setAttribute('required', 'required');
                            if (!nameInput.value) {
                                nameInput.value = '';
                            }
                        }
                    });
                }
            });
            
            // Make function globally accessible
            window.openPurchaseModal = function(itemId, itemName) {
                console.log('=== openPurchaseModal called ===');
                console.log('Item ID:', itemId);
                console.log('Item Name:', itemName);
                
                const modal = document.getElementById('purchaseModal');
                const form = document.getElementById('purchaseForm');
                
                if (!modal) {
                    console.error('Purchase modal not found!');
                    if (typeof showAlert === 'function') {
                        showAlert('Purchase modal not found. Please refresh the page.', 'error');
                    } else {
                        alert('Purchase modal not found. Please refresh the page.');
                    }
                    return;
                }
                
                if (!form) {
                    console.error('Purchase form not found!');
                    if (typeof showAlert === 'function') {
                        showAlert('Purchase form not found. Please refresh the page.', 'error');
                    } else {
                        alert('Purchase form not found. Please refresh the page.');
                    }
                    return;
                }
                
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.getElementById('purchaseItemName').textContent = itemName;
                
                // Set form action correctly
                // Route: /shopping-list/{item}/purchased
                const familyId = {{ $family->id }};
                form.action = `/shopping-list/${itemId}/purchased`;
                
                // Ensure family_id is in the form
                let familyIdInput = form.querySelector('input[name="family_id"]');
                if (!familyIdInput) {
                    familyIdInput = document.createElement('input');
                    familyIdInput.type = 'hidden';
                    familyIdInput.name = 'family_id';
                    familyIdInput.value = familyId;
                    form.appendChild(familyIdInput);
                } else {
                    familyIdInput.value = familyId;
                }
                
                // Ensure form has correct method
                let methodInput = form.querySelector('input[name="_method"]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PATCH';
                    form.insertBefore(methodInput, form.firstChild);
                } else {
                    methodInput.value = 'PATCH';
                }
                
                // Ensure CSRF token exists
                let csrfInput = form.querySelector('input[name="_token"]');
                if (!csrfInput) {
                    csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                    form.insertBefore(csrfInput, form.firstChild);
                }
                
                // Reset form fields
                const amountInput = form.querySelector('#amount');
                const budgetSelect = form.querySelector('#budget_id');
                if (amountInput) amountInput.value = '';
                if (budgetSelect) budgetSelect.value = '';
                
                console.log('Purchase modal opened for item:', itemId);
                console.log('Form action:', form.action);
                console.log('Form method:', methodInput?.value);
                console.log('Family ID:', familyIdInput?.value);
            }
            
            // Handle form submission using fetch to ensure it works
            document.addEventListener('DOMContentLoaded', function() {
                const purchaseForm = document.getElementById('purchaseForm');
                if (purchaseForm) {
                    purchaseForm.addEventListener('submit', async function(e) {
                        e.preventDefault(); // Prevent default form submission
                        
                        const submitBtn = this.querySelector('button[type="submit"]');
                        const originalText = submitBtn ? submitBtn.textContent : 'Mark Purchased';
                        
                        // Show loading state
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.textContent = 'Processing...';
                        }
                        
                        try {
                            // Get form data
                            const formData = new FormData(this);
                            
                            // Get CSRF token
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                                             this.querySelector('input[name="_token"]')?.value;
                            
                            // Get the action URL
                            const actionUrl = this.action;
                            
                            if (!actionUrl || actionUrl === '' || actionUrl === window.location.pathname) {
                                console.error('Invalid form action URL:', actionUrl);
                                throw new Error('Form action URL is not set correctly');
                            }
                            
                            console.log('=== Form Submission ===');
                            console.log('Action URL:', actionUrl);
                            console.log('Form data:', Object.fromEntries(formData.entries()));
                            console.log('CSRF Token:', csrfToken ? 'Present' : 'Missing');
                            
                            // Submit using fetch
                            const response = await fetch(actionUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                                body: formData
                            });
                            
                            console.log('Response status:', response.status);
                            console.log('Response ok:', response.ok);
                            
                            if (response.ok) {
                                // Success - reload page to show updated list
                                const result = await response.json().catch(() => null);
                                console.log('Success response:', result);
                                window.location.reload();
                            } else {
                                // Handle error
                                let errorMessage = 'Failed to mark item as purchased';
                                try {
                                    const errorData = await response.json();
                                    errorMessage = errorData.message || errorData.error || errorMessage;
                                    console.error('Error response:', errorData);
                                } catch (e) {
                                    const text = await response.text();
                                    console.error('Error response text:', text);
                                }
                                
                                // Use window.showAlert if available, otherwise alert
                                if (typeof window.showAlert === 'function') {
                                    window.showAlert(errorMessage, 'error');
                                } else if (typeof showAlert === 'function') {
                                    showAlert(errorMessage, 'error');
                                } else {
                                    alert(errorMessage);
                                }
                                
                                // Re-enable button
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.textContent = originalText;
                                }
                            }
                        } catch (error) {
                            console.error('=== Form Submission Error ===');
                            console.error('Error:', error);
                            console.error('Error message:', error.message);
                            console.error('Error stack:', error.stack);
                            
                            const errorMessage = 'An error occurred while marking the item as purchased. Please try again.';
                            
                            // Use window.showAlert if available, otherwise alert
                            if (typeof window.showAlert === 'function') {
                                window.showAlert(errorMessage, 'error');
                            } else if (typeof showAlert === 'function') {
                                showAlert(errorMessage, 'error');
                            } else {
                                alert(errorMessage);
                            }
                            
                            // Re-enable button
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = originalText;
                            }
                        }
                    });
                }
            });
            
            function closePurchaseModal() {
                const modal = document.getElementById('purchaseModal');
                const form = document.getElementById('purchaseForm');
                
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
                
                if (form) {
                    form.reset();
                }
            }
            
            // Close modal on outside click
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('purchaseModal');
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            closePurchaseModal();
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>

