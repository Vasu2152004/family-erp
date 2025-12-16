@php
    $serviceLog = $serviceLog ?? null;
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <x-label for="service_date" required>Service Date</x-label>
        <x-input type="date" name="service_date" id="service_date" value="{{ old('service_date', $serviceLog?->service_date?->format('Y-m-d') ?? '') }}" required class="mt-1" />
        @error('service_date')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="odometer_reading" required>Odometer Reading (km)</x-label>
        <x-input type="number" name="odometer_reading" id="odometer_reading" value="{{ old('odometer_reading', $serviceLog?->odometer_reading ?? '') }}" required min="0" class="mt-1" />
        @error('odometer_reading')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="cost" required>Cost (₹)</x-label>
        <x-input type="number" name="cost" id="cost" value="{{ old('cost', $serviceLog?->cost ?? '') }}" required min="0" step="0.01" class="mt-1" />
        @error('cost')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="service_type" required>Service Type</x-label>
        <select name="service_type" id="service_type" required class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="regular_service" {{ old('service_type', $serviceLog?->service_type ?? 'regular_service') == 'regular_service' ? 'selected' : '' }}>Regular Service</option>
            <option value="major_service" {{ old('service_type', $serviceLog?->service_type ?? '') == 'major_service' ? 'selected' : '' }}>Major Service</option>
            <option value="repair" {{ old('service_type', $serviceLog?->service_type ?? '') == 'repair' ? 'selected' : '' }}>Repair</option>
            <option value="other" {{ old('service_type', $serviceLog?->service_type ?? '') == 'other' ? 'selected' : '' }}>Other</option>
        </select>
        @error('service_type')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="service_center_name">Service Center Name</x-label>
        <x-input type="text" name="service_center_name" id="service_center_name" value="{{ old('service_center_name', $serviceLog?->service_center_name ?? '') }}" class="mt-1" />
        @error('service_center_name')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="service_center_contact">Service Center Contact</x-label>
        <x-input type="text" name="service_center_contact" id="service_center_contact" value="{{ old('service_center_contact', $serviceLog?->service_center_contact ?? '') }}" class="mt-1" />
        @error('service_center_contact')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="next_service_due_date">Next Service Due Date</x-label>
        <x-input type="date" name="next_service_due_date" id="next_service_due_date" value="{{ old('next_service_due_date', $serviceLog?->next_service_due_date?->format('Y-m-d') ?? '') }}" class="mt-1" />
        @error('next_service_due_date')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="next_service_odometer">Next Service Odometer (km)</x-label>
        <x-input type="number" name="next_service_odometer" id="next_service_odometer" value="{{ old('next_service_odometer', $serviceLog?->next_service_odometer ?? '') }}" min="0" class="mt-1" />
        @error('next_service_odometer')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <x-label for="description">Description</x-label>
        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('description', $serviceLog?->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>
</div>

