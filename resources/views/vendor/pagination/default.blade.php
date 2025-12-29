@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-2">
        <ul class="flex items-center gap-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="px-3 py-2 text-sm font-medium text-[var(--color-text-tertiary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg cursor-not-allowed opacity-50" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')" class="px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] transition-colors">&lsaquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="disabled" aria-disabled="true">
                        <span class="px-3 py-2 text-sm font-medium text-[var(--color-text-tertiary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page">
                                <span class="px-3 py-2 text-sm font-medium text-white bg-[var(--color-primary)] border border-[var(--color-primary)] rounded-lg">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] transition-colors">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')" class="px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] transition-colors">&rsaquo;</a>
                </li>
            @else
                <li class="disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="px-3 py-2 text-sm font-medium text-[var(--color-text-tertiary)] bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg cursor-not-allowed opacity-50" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
