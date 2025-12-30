@props([
    'field' => '',
    'label' => '',
    'required' => false,
    'helpText' => '',
])

<div class="form-field">
    @if($label)
        <x-label :for="$field" :required="$required">
            {{ $label }}
        </x-label>
    @endif
    
    {{ $slot }}
    
    <x-error-message :field="$field" />
    
    @if($helpText)
        <p class="mt-1 text-xs text-[var(--color-text-secondary)]">
            {{ $helpText }}
        </p>
    @endif
</div>


