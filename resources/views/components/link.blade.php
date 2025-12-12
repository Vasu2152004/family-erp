@props([
    'href' => '#',
    'variant' => 'primary',
])

@php
    $variantClasses = [
        'primary' => 'text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]',
        'secondary' => 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]',
    ];
    
    $classes = 'font-medium transition-colors ' . $variantClasses[$variant];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>







