@props([
    'field' => '',
])

@if($field && $errors->has($field))
    <p class="mt-2 text-sm font-medium text-[var(--color-error)]">
        {{ $errors->first($field) }}
    </p>
@elseif($slot->isNotEmpty())
    <p class="mt-2 text-sm font-medium text-[var(--color-error)]">
        {{ $slot }}
    </p>
@endif










