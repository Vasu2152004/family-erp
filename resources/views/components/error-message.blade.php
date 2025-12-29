@props([
    'field' => '',
    'icon' => true,
])

@if($field && $errors->has($field))
    <div class="mt-2 flex items-start gap-2 animate-fade-in" role="alert" aria-live="polite">
        @if($icon)
            <svg class="w-5 h-5 text-[var(--color-error)] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        @endif
        <p class="text-sm font-medium text-[var(--color-error)] leading-relaxed">
            {{ $errors->first($field) }}
        </p>
    </div>
@elseif($slot->isNotEmpty())
    <div class="mt-2 flex items-start gap-2 animate-fade-in" role="alert" aria-live="polite">
        @if($icon)
            <svg class="w-5 h-5 text-[var(--color-error)] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        @endif
        <p class="text-sm font-medium text-[var(--color-error)] leading-relaxed">
            {{ $slot }}
        </p>
    </div>
@endif
