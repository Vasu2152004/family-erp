@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variantClasses = [
        'primary' => 'bg-[var(--color-primary)] text-white border border-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] hover:border-[var(--color-primary-dark)] focus:ring-[var(--color-primary)] shadow-sm',
        'secondary' => 'bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] border border-[var(--color-border-primary)] hover:bg-[var(--color-bg-primary)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] focus:ring-[var(--color-primary)] shadow-sm',
        'outline' => 'border border-[var(--color-border-primary)] text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] hover:bg-[var(--color-bg-secondary)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] focus:ring-[var(--color-primary)] shadow-sm',
        'ghost' => 'text-[var(--color-text-secondary)] border border-transparent hover:border-[var(--color-border-primary)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] focus:ring-[var(--color-primary)]',
        'danger' => 'bg-red-600 text-white border border-red-600 hover:bg-red-700 hover:border-red-700 focus:ring-red-500 shadow-sm',
        'danger-outline' => 'border border-red-600 text-red-600 bg-transparent hover:bg-red-50 hover:border-red-700 hover:text-red-700 focus:ring-red-500 shadow-sm',
    ];

    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg',
    ];

    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>

