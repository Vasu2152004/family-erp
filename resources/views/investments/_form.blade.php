@php
    $isEdit = isset($investment) && $investment->exists;
@endphp

<div class="space-y-6">
    <div>
        <x-label for="family_member_id">Owner</x-label>
        <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">No Owner (Family Investment)</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" {{ old('family_member_id', isset($investment) ? ($investment->family_member_id ?? '') : '') == $member->id ? 'selected' : '' }}>
                    {{ $member->first_name }} {{ $member->last_name }}
                </option>
            @endforeach
        </select>
        <x-error-message field="family_member_id" />
    </div>

    <div>
        <x-label for="investment_type" required>Investment Type</x-label>
        <select name="investment_type" id="investment_type" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">Select Type</option>
            <option value="FD" {{ old('investment_type', isset($investment) ? ($investment->investment_type ?? '') : '') == 'FD' ? 'selected' : '' }}>Fixed Deposit (FD)</option>
            <option value="RD" {{ old('investment_type', isset($investment) ? ($investment->investment_type ?? '') : '') == 'RD' ? 'selected' : '' }}>Recurring Deposit (RD)</option>
            <option value="SIP" {{ old('investment_type', isset($investment) ? ($investment->investment_type ?? '') : '') == 'SIP' ? 'selected' : '' }}>SIP</option>
            <option value="MUTUAL_FUND" {{ old('investment_type', isset($investment) ? ($investment->investment_type ?? '') : '') == 'MUTUAL_FUND' ? 'selected' : '' }}>Mutual Fund</option>
            <option value="STOCK" {{ old('investment_type', isset($investment) ? ($investment->investment_type ?? '') : '') == 'STOCK' ? 'selected' : '' }}>Stock</option>
            <option value="CRYPTO" {{ old('investment_type', isset($investment) ? ($investment->investment_type ?? '') : '') == 'CRYPTO' ? 'selected' : '' }}>Crypto</option>
            <option value="OTHER" {{ old('investment_type', isset($investment) ? ($investment->investment_type ?? '') : '') == 'OTHER' ? 'selected' : '' }}>Other</option>
        </select>
        <x-error-message field="investment_type" />
    </div>

    <div>
        <x-label for="name" required>Investment Name</x-label>
        <x-input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', isset($investment) ? ($investment->name ?? '') : '') }}"
            placeholder="e.g., HDFC FD, SBI Mutual Fund"
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
            placeholder="Additional details about the investment"
        >{{ old('description', $investment->description ?? '') }}</textarea>
        <x-error-message field="description" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div id="amount_field">
            <x-label for="amount" id="amount_label">Investment Amount</x-label>
            <x-input
                type="number"
                name="amount"
                id="amount"
                value="{{ old('amount', isset($investment) ? ($investment->amount ?? '') : '') }}"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="mt-1"
            />
            <x-error-message field="amount" />
        </div>

        <div>
            <x-label for="start_date">Start Date</x-label>
            <x-input
                type="date"
                name="start_date"
                id="start_date"
                value="{{ old('start_date', isset($investment) && $investment->start_date ? $investment->start_date->format('Y-m-d') : '') }}"
                class="mt-1"
            />
            <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Required for FD, RD, SIP to calculate current value</p>
            <x-error-message field="start_date" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="interest_fields">
        <div>
            <x-label for="interest_rate">Interest Rate (%)</x-label>
            <x-input
                type="number"
                name="interest_rate"
                id="interest_rate"
                value="{{ old('interest_rate', isset($investment) ? ($investment->interest_rate ?? '') : '') }}"
                step="0.01"
                min="0"
                max="100"
                placeholder="0.00"
                class="mt-1"
            />
            <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Annual interest rate</p>
            <x-error-message field="interest_rate" />
        </div>

        <div>
            <x-label for="interest_period">Interest Period</x-label>
            <select name="interest_period" id="interest_period" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                <option value="">Select Period</option>
                <option value="YEARLY" {{ old('interest_period', isset($investment) ? ($investment->interest_period ?? '') : '') == 'YEARLY' ? 'selected' : '' }}>Yearly</option>
                <option value="MONTHLY" {{ old('interest_period', isset($investment) ? ($investment->interest_period ?? '') : '') == 'MONTHLY' ? 'selected' : '' }}>Monthly</option>
                <option value="QUARTERLY" {{ old('interest_period', isset($investment) ? ($investment->interest_period ?? '') : '') == 'QUARTERLY' ? 'selected' : '' }}>Quarterly</option>
            </select>
            <x-error-message field="interest_period" />
        </div>

        <div id="monthly_premium_field">
            <x-label for="monthly_premium">Monthly Premium</x-label>
            <x-input
                type="number"
                name="monthly_premium"
                id="monthly_premium"
                value="{{ old('monthly_premium', isset($investment) ? ($investment->monthly_premium ?? '') : '') }}"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="mt-1"
            />
            <p class="mt-1 text-xs text-[var(--color-text-secondary)]">For RD and SIP</p>
            <x-error-message field="monthly_premium" />
        </div>
    </div>

    <div>
        <x-label for="current_value">Current Value</x-label>
        <x-input
            type="number"
            name="current_value"
            id="current_value"
            value="{{ old('current_value', isset($investment) ? ($investment->current_value ?? '') : '') }}"
            step="0.01"
            min="0"
            placeholder="0.00"
            class="mt-1"
        />
        <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Auto-calculated if start date and interest rate provided. Leave empty for manual entry.</p>
        <x-error-message field="current_value" />
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
        <x-error-message field="details" />
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
                        {{ old('is_hidden', isset($investment) && $investment->is_hidden ? 'checked' : '') }}
                        onchange="document.getElementById('pin_field').classList.toggle('hidden', !this.checked)"
                        class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                        disabled
                    />
                    <span class="ml-2 text-sm text-[var(--color-text-primary)]">Mark as Hidden Investment</span>
                </label>
                <p class="text-xs text-[var(--color-text-secondary)]">
                    <span id="hidden_note">Family Investments cannot be hidden. Select an owner to enable this option.</span>
                </p>
            </div>
            <x-error-message field="is_hidden" />
        </div>

        <div id="pin_field" class="hidden">
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
            <x-error-message field="pin" />
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const investmentType = document.getElementById('investment_type');
    const monthlyPremiumField = document.getElementById('monthly_premium_field');
    const interestFields = document.getElementById('interest_fields');
    const familyMemberId = document.getElementById('family_member_id');
    const isHiddenCheckbox = document.getElementById('is_hidden');
    const hiddenNote = document.getElementById('hidden_note');
    const pinField = document.getElementById('pin_field');
    
    function toggleFields() {
        const type = investmentType.value;
        const amountField = document.getElementById('amount_field');
        const amountInput = document.getElementById('amount');
        const amountLabel = document.getElementById('amount_label');
        
        if (type === 'SIP') {
            // For SIP, hide amount field and make it optional
            amountField.style.display = 'none';
            amountInput.removeAttribute('required');
            amountInput.value = ''; // Clear value
            if (amountLabel) {
                amountLabel.classList.remove('required');
                amountLabel.querySelector('span')?.remove(); // Remove required asterisk if exists
            }
            monthlyPremiumField.style.display = 'block';
        } else if (type === 'RD') {
            // For RD, show both but monthly premium is primary
            amountField.style.display = 'block';
            amountInput.setAttribute('required', 'required');
            if (amountLabel && !amountLabel.querySelector('.required-indicator')) {
                const span = document.createElement('span');
                span.className = 'text-red-500 ml-1';
                span.textContent = '*';
                span.classList.add('required-indicator');
                amountLabel.appendChild(span);
            }
            monthlyPremiumField.style.display = 'block';
        } else {
            // For other types, show amount and hide monthly premium
            amountField.style.display = 'block';
            amountInput.setAttribute('required', 'required');
            if (amountLabel && !amountLabel.querySelector('.required-indicator')) {
                const span = document.createElement('span');
                span.className = 'text-red-500 ml-1';
                span.textContent = '*';
                span.classList.add('required-indicator');
                amountLabel.appendChild(span);
            }
            monthlyPremiumField.style.display = 'none';
        }
        
        if (type === 'FD' || type === 'RD' || type === 'SIP') {
            interestFields.style.display = 'grid';
        } else if (type === 'OTHER') {
            // For OTHER type, hide interest fields (no interest)
            interestFields.style.display = 'none';
        } else {
            interestFields.style.display = 'grid';
        }
    }
    
    function toggleHiddenField() {
        const hasOwner = familyMemberId.value !== '';
        
        if (hasOwner) {
            // Owner selected - enable hidden checkbox
            isHiddenCheckbox.disabled = false;
            hiddenNote.textContent = 'Hidden investments require PIN to unlock and details are encrypted';
        } else {
            // No owner - disable hidden checkbox and uncheck it
            isHiddenCheckbox.disabled = true;
            isHiddenCheckbox.checked = false;
            hiddenNote.textContent = 'Family Investments cannot be hidden. Select an owner to enable this option.';
            pinField.classList.add('hidden');
        }
    }
    
    investmentType.addEventListener('change', toggleFields);
    familyMemberId.addEventListener('change', toggleHiddenField);
    
    // Initial call to set up fields based on current selection
    toggleFields();
    toggleHiddenField();
    
    // Also handle form submission to ensure amount is not required for SIP
    const form = document.querySelector('form.needs-validation');
    if (form) {
        form.addEventListener('submit', function(e) {
            const type = investmentType.value;
            const amountInput = document.getElementById('amount');
            
            // If SIP is selected, ensure amount is not required
            if (type === 'SIP') {
                amountInput.removeAttribute('required');
                if (!amountInput.value) {
                    amountInput.value = '0'; // Set to 0 if empty
                }
            }
        });
    }
});
</script>

