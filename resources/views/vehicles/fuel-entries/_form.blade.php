@php
    $fuelEntry = $fuelEntry ?? null;
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <x-label for="fill_date" required>Fill Date</x-label>
        <x-input type="date" name="fill_date" id="fill_date" value="{{ old('fill_date', $fuelEntry?->fill_date?->format('Y-m-d') ?? '') }}" required class="mt-1" />
        <x-error-message field="fill_date" />
    </div>

    <div>
        <x-label for="odometer_reading" required>Odometer Reading (km)</x-label>
        <x-input type="number" name="odometer_reading" id="odometer_reading" value="{{ old('odometer_reading', $fuelEntry?->odometer_reading ?? '') }}" required min="0" class="mt-1" />
        <x-error-message field="odometer_reading" />
    </div>

    <div>
        <x-label for="fuel_amount" required>Fuel Amount (Liters)</x-label>
        <x-input type="number" name="fuel_amount" id="fuel_amount" value="{{ old('fuel_amount', $fuelEntry?->fuel_amount ?? '') }}" required min="0.01" step="0.01" class="mt-1" />
        <x-error-message field="fuel_amount" />
    </div>

    <div>
        <x-label for="cost" required>Cost (₹)</x-label>
        <x-input type="number" name="cost" id="cost" value="{{ old('cost', $fuelEntry?->cost ?? '') }}" required min="0" step="0.01" class="mt-1" />
        <x-error-message field="cost" />
    </div>

    <div>
        <x-label for="fuel_type" required>Fuel Type</x-label>
        <select name="fuel_type" id="fuel_type" required class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="petrol" {{ old('fuel_type', $fuelEntry?->fuel_type ?? 'petrol') == 'petrol' ? 'selected' : '' }}>Petrol</option>
            <option value="diesel" {{ old('fuel_type', $fuelEntry?->fuel_type ?? '') == 'diesel' ? 'selected' : '' }}>Diesel</option>
        </select>
        <x-error-message field="fuel_type" />
    </div>

    <div>
        <x-label for="fuel_station_name">Fuel Station Name</x-label>
        <x-input type="text" name="fuel_station_name" id="fuel_station_name" value="{{ old('fuel_station_name', $fuelEntry?->fuel_station_name ?? '') }}" class="mt-1" />
        <x-error-message field="fuel_station_name" />
    </div>

    <div class="md:col-span-2">
        <x-label for="notes">Notes</x-label>
        <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('notes', $fuelEntry?->notes ?? '') }}</textarea>
        <x-error-message field="notes" />
    </div>

    @if(isset($budgets) && $budgets->isNotEmpty())
    <div class="md:col-span-2 pt-4 border-t border-[var(--color-border-primary)]">
        @if($fuelEntry?->transaction_id)
            <p class="text-sm text-[var(--color-text-secondary)]">Already linked to a transaction.</p>
        @else
        <label class="flex items-center gap-2 mb-3">
            <input type="checkbox" name="create_transaction" id="create_transaction" value="1" {{ old('create_transaction') ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
            <span class="text-sm font-medium text-[var(--color-text-primary)]">Create transaction for this expense</span>
        </label>
        <div id="budget_field" class="mt-2" style="display: {{ old('create_transaction') ? 'block' : 'none' }};">
            <x-label for="budget_id">Budget</x-label>
            <select name="budget_id" id="budget_id" class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                <option value="">Select budget</option>
                @foreach($budgets as $budget)
                    <option value="{{ $budget->id }}" {{ old('budget_id') == $budget->id ? 'selected' : '' }}>
                        {{ $budget->category->name ?? 'Uncategorized' }}
                        @if($budget->family_member_id)(Personal)@else(Family)@endif
                        - ₹{{ number_format($budget->amount, 2) }}
                    </option>
                @endforeach
            </select>
            <x-error-message field="budget_id" />
        </div>
        @endif
    </div>
    @endif
</div>

@if(isset($budgets) && $budgets->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', function() {
    const createTransactionCheckbox = document.getElementById('create_transaction');
    const budgetField = document.getElementById('budget_field');
    if (createTransactionCheckbox && budgetField) {
        createTransactionCheckbox.addEventListener('change', function() {
            budgetField.style.display = this.checked ? 'block' : 'none';
        });
    }
});
</script>
@endif

