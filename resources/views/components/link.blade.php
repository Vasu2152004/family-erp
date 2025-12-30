@props([
    'href' => '#',
    'variant' => 'primary',
    'useJsNav' => false, // For auth links to bypass form validation
])

@php
    $variantClasses = [
        'primary' => 'text-[var(--color-primary)] hover:text-[var(--color-primary-light)] underline underline-offset-4 decoration-2 decoration-[var(--color-primary-light)]',
        'secondary' => 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]',
    ];
    
    $classes = 'font-semibold transition-colors ' . ($variantClasses[$variant] ?? $variantClasses['primary']);
    
    // Auto-detect if we're in auth layout and link is in footer
    $isAuthPage = request()->routeIs('login', 'register', 'forgot-password');
    $useJsNav = $useJsNav || ($isAuthPage && !$attributes->has('data-in-form'));
@endphp

<a 
    href="{{ $href }}" 
    @if($useJsNav)
        onclick="event.preventDefault(); event.stopPropagation(); event.stopImmediatePropagation(); window.location.href = '{{ $href }}'; return false;"
    @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</a>










