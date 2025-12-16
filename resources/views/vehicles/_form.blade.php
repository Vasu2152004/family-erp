@php
    $vehicle = $vehicle ?? null;
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="md:col-span-2">
        <x-label for="registration_number" required>Registration Number (RC)</x-label>
        <x-input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number', $vehicle?->registration_number ?? '') }}" required placeholder="e.g., MH12AB1234" class="mt-1" />
        @error('registration_number')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="make" required>Make</x-label>
        <x-input type="text" name="make" id="make" value="{{ old('make', $vehicle?->make ?? '') }}" required placeholder="e.g., Toyota, Honda" class="mt-1" />
        @error('make')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="model" required>Model</x-label>
        <x-input type="text" name="model" id="model" value="{{ old('model', $vehicle?->model ?? '') }}" required placeholder="e.g., Camry, Civic" class="mt-1" />
        @error('model')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="year" required>Year</x-label>
        <x-input type="number" name="year" id="year" value="{{ old('year', $vehicle?->year ?? '') }}" required min="1900" max="{{ date('Y') + 1 }}" class="mt-1" />
        @error('year')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="color">Color</x-label>
        <x-input type="text" name="color" id="color" value="{{ old('color', $vehicle?->color ?? '') }}" placeholder="e.g., Red, Blue" class="mt-1" />
        @error('color')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="fuel_type" required>Fuel Type</x-label>
        <select name="fuel_type" id="fuel_type" required class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="petrol" {{ old('fuel_type', $vehicle?->fuel_type ?? 'petrol') == 'petrol' ? 'selected' : '' }}>Petrol</option>
            <option value="diesel" {{ old('fuel_type', $vehicle?->fuel_type ?? '') == 'diesel' ? 'selected' : '' }}>Diesel</option>
            <option value="electric" {{ old('fuel_type', $vehicle?->fuel_type ?? '') == 'electric' ? 'selected' : '' }}>Electric</option>
            <option value="hybrid" {{ old('fuel_type', $vehicle?->fuel_type ?? '') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
        </select>
        @error('fuel_type')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="family_member_id">Owner/Driver</x-label>
        <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">Unassigned</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" {{ old('family_member_id', $vehicle?->family_member_id ?? '') == $member->id ? 'selected' : '' }}>
                    {{ $member->first_name }} {{ $member->last_name }}
                </option>
            @endforeach
        </select>
        @error('family_member_id')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="rc_expiry_date">RC Expiry Date</x-label>
        <x-input type="date" name="rc_expiry_date" id="rc_expiry_date" value="{{ old('rc_expiry_date', $vehicle?->rc_expiry_date?->format('Y-m-d') ?? '') }}" class="mt-1" />
        @error('rc_expiry_date')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="insurance_expiry_date">Insurance Expiry Date</x-label>
        <x-input type="date" name="insurance_expiry_date" id="insurance_expiry_date" value="{{ old('insurance_expiry_date', $vehicle?->insurance_expiry_date?->format('Y-m-d') ?? '') }}" class="mt-1" />
        @error('insurance_expiry_date')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="puc_expiry_date">PUC Expiry Date</x-label>
        <x-input type="date" name="puc_expiry_date" id="puc_expiry_date" value="{{ old('puc_expiry_date', $vehicle?->puc_expiry_date?->format('Y-m-d') ?? '') }}" class="mt-1" />
        @error('puc_expiry_date')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>
</div>

