@props([
    'type' => 'text',
    'name' => '',
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'autofocus' => false,
    'error' => false,
])

@php
    $inputId = $id ?? $name;
    $baseClasses = 'block w-full rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0';
    $errorClasses = $error ? 'border-[var(--color-error)] focus:ring-[var(--color-error)] focus:border-[var(--color-error)]' : 'border-[var(--color-border-primary)] focus:ring-[var(--color-border-focus)] focus:border-[var(--color-border-focus)]';
    $classes = $baseClasses . ' ' . $errorClasses . ' px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]';
@endphp

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $inputId }}"
    value="{{ old($name, $value) }}"
    placeholder="{{ $placeholder }}"
    {{ $required ? 'required' : '' }}
    {{ $autofocus ? 'autofocus' : '' }}
    {{ $attributes->merge(['class' => $classes]) }}
>

