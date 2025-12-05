@props([
    'field' => '',
])

@if($field && $errors->has($field))
    <p class="mt-1 text-sm text-[var(--color-error)]">
        {{ $errors->first($field) }}
    </p>
@elseif($slot->isNotEmpty())
    <p class="mt-1 text-sm text-[var(--color-error)]">
        {{ $slot }}
    </p>
@endif

