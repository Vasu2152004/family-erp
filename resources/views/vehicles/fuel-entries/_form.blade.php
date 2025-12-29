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
</div>

