@props([
    'label' => '',
    'labelFor' => '',
    'required' => false,
    'error' => false,
    'helpText' => '',
])

<div {{ $attributes->merge(['class' => '']) }}>
    @if($label)
        <x-label for="{{ $labelFor }}" :required="$required">
            {{ $label }}
        </x-label>
    @endif
    
    {{ $slot }}
    
    @if($helpText)
        <p class="mt-1 text-xs text-[var(--color-text-secondary)]">
            {{ $helpText }}
        </p>
    @endif
</div>
















