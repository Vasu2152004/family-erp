@props([
    'for' => '',
    'required' => false,
])

@php
    $baseClasses = 'block text-sm font-semibold text-[var(--color-text-secondary)] uppercase tracking-wide mb-2';
    $classes = $baseClasses;
@endphp

<label for="{{ $for }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
    @if($required)
        <span class="text-[var(--color-error)]">*</span>
    @endif
</label>

