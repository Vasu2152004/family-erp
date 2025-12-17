@php
    $isEdit = isset($investment) && $investment->exists;
@endphp

<div class="space-y-6">
    <div>
        <x-label for="family_member_id">Owner</x-label>
        <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">No Owner (Family Investment)</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" {{ old('family_member_id', $investment->family_member_id ?? '') == $member->id ? 'selected' : '' }}>
                    {{ $member->first_name }} {{ $member->last_name }}
                </option>
            @endforeach
        </select>
        @error('family_member_id')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="investment_type" required>Investment Type</x-label>
        <select name="investment_type" id="investment_type" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">Select Type</option>
            <option value="FD" {{ old('investment_type', $investment->investment_type ?? '') == 'FD' ? 'selected' : '' }}>Fixed Deposit (FD)</option>
            <option value="RD" {{ old('investment_type', $investment->investment_type ?? '') == 'RD' ? 'selected' : '' }}>Recurring Deposit (RD)</option>
            <option value="SIP" {{ old('investment_type', $investment->investment_type ?? '') == 'SIP' ? 'selected' : '' }}>SIP</option>
            <option value="MUTUAL_FUND" {{ old('investment_type', $investment->investment_type ?? '') == 'MUTUAL_FUND' ? 'selected' : '' }}>Mutual Fund</option>
            <option value="STOCK" {{ old('investment_type', $investment->investment_type ?? '') == 'STOCK' ? 'selected' : '' }}>Stock</option>
            <option value="CRYPTO" {{ old('investment_type', $investment->investment_type ?? '') == 'CRYPTO' ? 'selected' : '' }}>Crypto</option>
            <option value="OTHER" {{ old('investment_type', $investment->investment_type ?? '') == 'OTHER' ? 'selected' : '' }}>Other</option>
        </select>
        @error('investment_type')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="name" required>Investment Name</x-label>
        <x-input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $investment->name ?? '') }}"
            placeholder="e.g., HDFC FD, SBI Mutual Fund"
            required
            class="mt-1"
        />
        @error('name')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-label for="description">Description</x-label>
        <textarea
            name="description"
            id="description"
            rows="3"
            class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
            placeholder="Additional details about the investment"
        >{{ old('description', $investment->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-label for="amount" required>Investment Amount</x-label>
            <x-input
                type="number"
                name="amount"
                id="amount"
                value="{{ old('amount', $investment->amount ?? '') }}"
                step="0.01"
                min="0"
                placeholder="0.00"
                required
                class="mt-1"
            />
            @error('amount')
                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-label for="current_value">Current Value</x-label>
            <x-input
                type="number"
                name="current_value"
                id="current_value"
                value="{{ old('current_value', $investment->current_value ?? '') }}"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="mt-1"
            />
            <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Optional: Current market value</p>
            @error('current_value')
                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <x-label for="details">Investment Details</x-label>
        <textarea
            name="details"
            id="details"
            rows="5"
            class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
            placeholder="Account number, policy details, broker information, etc."
        >{{ old('details', isset($investment) && $investment->details ? $investment->details : (isset($investment) && $investment->encrypted_details ? '[Encrypted - Hidden Investment]' : '')) }}</textarea>
        <p class="mt-1 text-xs text-[var(--color-text-secondary)]">This information will be encrypted if investment is marked as hidden</p>
        @error('details')
            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
        @enderror
    </div>

    @if(!$isEdit)
        <div>
            <x-label for="is_hidden">Visibility</x-label>
            <div class="mt-2 space-y-2">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        name="is_hidden"
                        id="is_hidden"
                        value="1"
                        {{ old('is_hidden') ? 'checked' : '' }}
                        onchange="document.getElementById('pin_field').classList.toggle('hidden', !this.checked)"
                        class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                    />
                    <span class="ml-2 text-sm text-[var(--color-text-primary)]">Mark as Hidden Investment</span>
                </label>
                <p class="text-xs text-[var(--color-text-secondary)]">Hidden investments require PIN to unlock and details are encrypted</p>
            </div>
            @error('is_hidden')
                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
            @enderror
        </div>

        <div id="pin_field" class="{{ old('is_hidden') ? '' : 'hidden' }}">
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
            <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Required for hidden investments. Keep this PIN safe.</p>
            @error('pin')
                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>

