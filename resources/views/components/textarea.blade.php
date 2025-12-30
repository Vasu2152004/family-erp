@props([
    'name' => '',
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'rows' => 3,
    'error' => false,
    'validate' => true,
])

@php
    $textareaId = $id ?? $name;
    $hasError = $error || ($errors->has($name) ?? false);
    $baseClasses = 'block w-full rounded-lg border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 shadow-sm resize-y';
    $errorClasses = $hasError ? 'border-[var(--color-error)] focus:ring-[var(--color-error)] focus:border-[var(--color-error)]' : 'border-[var(--color-border-primary)] focus:ring-[var(--color-border-focus)] focus:border-[var(--color-border-focus)]';
    $classes = $baseClasses . ' ' . $errorClasses . ' px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)]';
@endphp

<textarea
    name="{{ $name }}"
    id="{{ $textareaId }}"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    {{ $required ? 'required' : '' }}
    {{ $attributes->merge(['class' => $classes]) }}
>{{ old($name, $value) }}</textarea>


