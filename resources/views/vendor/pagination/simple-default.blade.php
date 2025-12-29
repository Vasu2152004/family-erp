@if ($paginator->hasPages())
    <nav>
        <ul class="flex items-center gap-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true">
                    <span class="px-3 py-2 text-sm font-medium text-[var(--color-text-tertiary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg cursor-not-allowed opacity-50">@lang('pagination.previous')</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] transition-colors">@lang('pagination.previous')</a>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] transition-colors">@lang('pagination.next')</a>
                </li>
            @else
                <li class="disabled" aria-disabled="true">
                    <span class="px-3 py-2 text-sm font-medium text-[var(--color-text-tertiary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg cursor-not-allowed opacity-50">@lang('pagination.next')</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
