@props([
    'name' => '',
    'id' => null,
    'required' => false,
    'error' => false,
    'validate' => true,
])

@php
    $selectId = $id ?? $name;
    $hasError = $error || ($errors->has($name) ?? false);
    $baseClasses = 'block w-full rounded-lg border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 shadow-sm';
    $errorClasses = $hasError ? 'border-[var(--color-error)] focus:ring-[var(--color-error)] focus:border-[var(--color-error)]' : 'border-[var(--color-border-primary)] focus:ring-[var(--color-border-focus)] focus:border-[var(--color-border-focus)]';
    $classes = $baseClasses . ' ' . $errorClasses . ' px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)]';
@endphp

<select
    name="{{ $name }}"
    id="{{ $selectId }}"
    {{ $required ? 'required' : '' }}
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</select>


