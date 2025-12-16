@props([
    'href' => '#',
    'variant' => 'primary',
])

@php
    $variantClasses = [
        'primary' => 'text-[var(--color-primary)] hover:text-[var(--color-primary-light)] underline underline-offset-4 decoration-2 decoration-[var(--color-primary-light)]',
        'secondary' => 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]',
    ];
    
    $classes = 'font-semibold transition-colors ' . ($variantClasses[$variant] ?? $variantClasses['primary']);
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>










