<x-app-layout title="Create Finance Account: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Finance Accounts', 'url' => route('finance.accounts.index', ['family_id' => $family->id])],
            ['label' => 'Create Account']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Create Finance Account</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Add a new finance account for {{ $family->name }}
                </p>
            </div>

        <x-form method="POST" action="{{ route('finance.accounts.store', ['family_id' => $family->id]) }}" class="space-y-6">
            @csrf
            <input type="hidden" name="family_id" value="{{ $family->id }}">

            <div>
                <x-label for="name" required>Account Name</x-label>
                <x-input 
                    type="text" 
                    name="name" 
                    id="name" 
                    value="{{ old('name') }}" 
                    placeholder="e.g., Main Bank Account"
                    required
                    autofocus
                    class="mt-1"
                />
                <x-error-message field="name" />
            </div>

            <div>
                <x-label for="type" required>Account Type</x-label>
                <select name="type" id="type" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="">Select account type...</option>
                    <option value="CASH" {{ old('type') == 'CASH' ? 'selected' : '' }}>CASH</option>
                    <option value="BANK" {{ old('type') == 'BANK' ? 'selected' : '' }}>BANK</option>
                    <option value="WALLET" {{ old('type') == 'WALLET' ? 'selected' : '' }}>WALLET</option>
                </select>
                <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Or enter a custom type</p>
                <x-input 
                    type="text" 
                    name="custom_type" 
                    id="custom_type" 
                    value="{{ old('custom_type') }}" 
                    placeholder="Custom type (e.g., Credit Card, Savings)"
                    class="mt-2"
                />
                <x-error-message field="type" />
            </div>

            <div>
                <x-label for="initial_balance">Initial Balance</x-label>
                <x-input 
                    type="number" 
                    name="initial_balance" 
                    id="initial_balance" 
                    value="{{ old('initial_balance', 0) }}" 
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    class="mt-1"
                />
                <x-error-message field="initial_balance" />
            </div>

            <div>
                <x-label for="description">Description</x-label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="3"
                    placeholder="Optional description for this account"
                    class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
                >{{ old('description') }}</textarea>
                <x-error-message field="description" />
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                <x-label for="is_active" class="ml-2">Active</x-label>
            </div>

            <div class="flex gap-4">
                <x-button type="submit" variant="primary" size="md">
                    Create Account
                </x-button>
                <a href="{{ route('finance.accounts.index', ['family_id' => $family->id]) }}">
                    <x-button type="button" variant="outline" size="md">
                        Cancel
                    </x-button>
                </a>
            </div>
        </x-form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const typeSelect = document.getElementById('type');
                const customTypeInput = document.getElementById('custom_type');
                
                customTypeInput.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        typeSelect.value = '';
                    }
                });
                
                typeSelect.addEventListener('change', function() {
                    if (this.value !== '') {
                        customTypeInput.value = '';
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>


