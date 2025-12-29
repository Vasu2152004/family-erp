@php
    $medicine = $medicine ?? null;
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="md:col-span-2">
        <x-label for="name" required>Medicine Name</x-label>
        <x-input type="text" name="name" id="name" value="{{ old('name', $medicine?->name ?? '') }}" required placeholder="e.g., Paracetamol 500mg" class="mt-1" />
        @error('name')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <x-label for="description">Description</x-label>
        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Additional notes or instructions...">{{ old('description', $medicine?->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="manufacturer">Manufacturer</x-label>
        <x-input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer', $medicine?->manufacturer ?? '') }}" placeholder="e.g., Cipla, Sun Pharma" class="mt-1" />
        @error('manufacturer')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="batch_number">Batch Number</x-label>
        <x-input type="text" name="batch_number" id="batch_number" value="{{ old('batch_number', $medicine?->batch_number ?? '') }}" placeholder="e.g., BATCH123456" class="mt-1" />
        @error('batch_number')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="quantity" required>Quantity</x-label>
        <x-input type="number" name="quantity" id="quantity" value="{{ old('quantity', $medicine?->quantity ?? '') }}" required min="0" step="0.01" placeholder="e.g., 100" class="mt-1" />
        @error('quantity')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="unit">Unit</x-label>
        <x-input type="text" name="unit" id="unit" value="{{ old('unit', $medicine?->unit ?? 'units') }}" placeholder="e.g., tablets, ml, bottles" class="mt-1" />
        @error('unit')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="min_stock_level">Minimum Stock Level</x-label>
        <x-input type="number" name="min_stock_level" id="min_stock_level" value="{{ old('min_stock_level', $medicine?->min_stock_level ?? '') }}" min="0" step="0.01" placeholder="Alert when below this" class="mt-1" />
        @error('min_stock_level')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="expiry_date">Expiry Date</x-label>
        <x-input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date', $medicine?->expiry_date?->format('Y-m-d') ?? '') }}" class="mt-1" />
        @error('expiry_date')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="purchase_date">Purchase Date</x-label>
        <x-input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', $medicine?->purchase_date?->format('Y-m-d') ?? '') }}" class="mt-1" />
        @error('purchase_date')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="purchase_price">Purchase Price</x-label>
        <x-input type="number" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $medicine?->purchase_price ?? '') }}" min="0" step="0.01" placeholder="0.00" class="mt-1" />
        @error('purchase_price')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="family_member_id">For Family Member (Optional)</x-label>
        <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">Family-wide (All members)</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" {{ old('family_member_id', $medicine?->family_member_id ?? '') == $member->id ? 'selected' : '' }}>
                    {{ $member->first_name }} {{ $member->last_name }}
                </option>
            @endforeach
        </select>
        @error('family_member_id')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="prescription_id">Link to Prescription (Optional)</x-label>
        <select name="prescription_id" id="prescription_id" class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">None</option>
            @foreach($prescriptions as $prescription)
                <option value="{{ $prescription->id }}" {{ old('prescription_id', $medicine?->prescription_id ?? '') == $prescription->id ? 'selected' : '' }}>
                    {{ $prescription->medication_name }} - {{ $prescription->familyMember ? $prescription->familyMember->first_name . ' ' . $prescription->familyMember->last_name : 'Family' }}
                </option>
            @endforeach
        </select>
        @error('prescription_id')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <x-label for="prescription_file">Prescription File (PDF/Image)</x-label>
        <input type="file" name="prescription_file" id="prescription_file" accept=".pdf,.png,.jpg,.jpeg" class="mt-1 block w-full text-sm text-[var(--color-text-primary)] file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-[var(--color-primary)] file:text-white hover:file:bg-[var(--color-primary-dark)]">
        @error('prescription_file')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
        @if($medicine?->prescription_file_path)
            <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                Current: <a href="{{ route('families.medicines.prescription.download', ['family' => $family->id, 'medicine' => $medicine->id]) }}" class="text-[var(--color-primary)] hover:underline" target="_blank">{{ $medicine->prescription_original_name ?? 'prescription.pdf' }}</a>
            </p>
        @endif
    </div>
</div>





