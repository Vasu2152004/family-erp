@props(['items' => []])

@if(count($items) > 0)
    <nav aria-label="Breadcrumb" class="mb-6">
        <ol class="flex items-center space-x-2 text-sm">
            @foreach($items as $index => $item)
                <li class="flex items-center">
                    @if($index > 0)
                        <svg class="w-4 h-4 text-[var(--color-text-secondary)] mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    @endif
                    @if(isset($item['url']) && $item['url'] && $index < count($items) - 1)
                        <a href="{{ $item['url'] }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="text-[var(--color-text-primary)] font-medium">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif

