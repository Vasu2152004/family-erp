@props([
    'name' => '',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'required' => false,
])

@php
    $inputId = $id ?? $name;
@endphp

<input
    type="checkbox"
    name="{{ $name }}"
    id="{{ $inputId }}"
    value="{{ $value }}"
    {{ $checked ? 'checked' : '' }}
    {{ $required ? 'required' : '' }}
    class="w-5 h-5 text-[var(--color-primary)] bg-[var(--color-surface)] border-[var(--color-border-primary)] rounded-md focus:ring-[var(--color-primary)] focus:ring-2"
    {{ $attributes }}
/>










