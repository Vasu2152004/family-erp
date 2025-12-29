@php
    $isEdit = isset($asset) && $asset->exists;
@endphp

<div class="space-y-6">
    <div>
        <x-label for="family_member_id">Owner</x-label>
        <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">No Owner (Family Asset)</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" {{ old('family_member_id', $asset->family_member_id ?? '') == $member->id ? 'selected' : '' }}>
                    {{ $member->first_name }} {{ $member->last_name }}
                </option>
            @endforeach
        </select>
        <x-error-message field="family_member_id" />
    </div>

    <div>
        <x-label for="asset_type" required>Asset Type</x-label>
        <select name="asset_type" id="asset_type" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">Select Type</option>
            <option value="PROPERTY" {{ old('asset_type', $asset->asset_type ?? '') == 'PROPERTY' ? 'selected' : '' }}>Property</option>
            <option value="GOLD" {{ old('asset_type', $asset->asset_type ?? '') == 'GOLD' ? 'selected' : '' }}>Gold</option>
            <option value="JEWELRY" {{ old('asset_type', $asset->asset_type ?? '') == 'JEWELRY' ? 'selected' : '' }}>Jewelry</option>
            <option value="LAND" {{ old('asset_type', $asset->asset_type ?? '') == 'LAND' ? 'selected' : '' }}>Land</option>
        </select>
        <x-error-message field="asset_type" />
    </div>

    <div>
        <x-label for="name" required>Asset Name</x-label>
        <x-input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $asset->name ?? '') }}"
            placeholder="e.g., House in Mumbai, Gold Jewelry Set"
            required
            class="mt-1"
        />
        <x-error-message field="name" />
    </div>

    <div>
        <x-label for="description">Description</x-label>
        <textarea
            name="description"
            id="description"
            rows="3"
            class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
            placeholder="Additional details about the asset"
        >{{ old('description', $asset->description ?? '') }}</textarea>
        <x-error-message field="description" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-label for="purchase_date">Purchase Date</x-label>
            <x-input
                type="date"
                name="purchase_date"
                id="purchase_date"
                value="{{ old('purchase_date', isset($asset) && $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : '') }}"
                class="mt-1"
            />
            <x-error-message field="purchase_date" />
        </div>

        <div>
            <x-label for="location">Location</x-label>
            <x-input
                type="text"
                name="location"
                id="location"
                value="{{ old('location', $asset->location ?? '') }}"
                placeholder="e.g., Mumbai, Maharashtra"
                class="mt-1"
            />
            <x-error-message field="location" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-label for="purchase_value">Purchase Value</x-label>
            <x-input
                type="number"
                name="purchase_value"
                id="purchase_value"
                value="{{ old('purchase_value', $asset->purchase_value ?? '') }}"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="mt-1"
            />
            <x-error-message field="purchase_value" />
        </div>

        <div>
            <x-label for="current_value">Current Value</x-label>
            <x-input
                type="number"
                name="current_value"
                id="current_value"
                value="{{ old('current_value', $asset->current_value ?? '') }}"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="mt-1"
            />
            <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Optional: Current market value</p>
            <x-error-message field="current_value" />
        </div>
    </div>

    <div>
        <x-label for="notes">Notes</x-label>
        <textarea
            name="notes"
            id="notes"
            rows="4"
            class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
            placeholder="Additional notes, documents reference, etc."
        >{{ old('notes', $asset->notes ?? '') }}</textarea>
        <x-error-message field="notes" />
    </div>

    @if(!$isEdit)
        <div>
            <x-label for="is_locked">Security</x-label>
            <div class="mt-2 space-y-2">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        name="is_locked"
                        id="is_locked"
                        value="1"
                        {{ old('is_locked') ? 'checked' : '' }}
                        onchange="document.getElementById('asset_pin_field').classList.toggle('hidden', !this.checked)"
                        class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                    />
                    <span class="ml-2 text-sm text-[var(--color-text-primary)]">Lock this asset with a PIN</span>
                </label>
                <p class="text-xs text-[var(--color-text-secondary)]">Locked assets require a PIN and notes are encrypted.</p>
            </div>
            <x-error-message field="is_locked" />
        </div>

        <div id="asset_pin_field" class="{{ old('is_locked') ? '' : 'hidden' }}">
            <x-label for="pin" required>PIN</x-label>
            <x-input
                type="password"
                name="pin"
                id="pin"
                minlength="4"
                maxlength="20"
                placeholder="Enter 4-20 character PIN"
                class="mt-1"
            />
            <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Required to lock the asset. Keep this PIN safe.</p>
            <x-error-message field="pin" />
        </div>
    @endif
</div>

