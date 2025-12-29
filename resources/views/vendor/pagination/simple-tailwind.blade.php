@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{!! __('Pagination Navigation') !!}" class="flex items-center justify-between gap-2">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-[var(--color-text-tertiary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] cursor-default rounded-lg opacity-50">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] transition-colors">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] transition-colors">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-[var(--color-text-tertiary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] cursor-default rounded-lg opacity-50">
                {!! __('pagination.next') !!}
            </span>
        @endif
    </nav>
@endif
