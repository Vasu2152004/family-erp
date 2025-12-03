@props([
    'for' => '',
    'required' => false,
])

@php
    $baseClasses = 'block text-sm font-medium text-[var(--color-text-primary)] mb-1.5';
    $classes = $baseClasses;
@endphp

<label for="{{ $for }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
    @if($required)
        <span class="text-[var(--color-error)]">*</span>
    @endif
</label>

