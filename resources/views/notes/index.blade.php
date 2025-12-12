<x-app-layout title="Notes">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Notes'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Notes / Diary</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">Shared, private, and locked family notes.</p>
                </div>
                @can('create', \App\Models\Note::class)
                    <a href="{{ route('families.notes.create', ['family' => $family->id]) }}">
                        <x-button variant="primary" size="md">Create Note</x-button>
                    </a>
                @endcan
            </div>

            <form method="GET" action="{{ route('families.notes.index', ['family' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-xl p-4 mt-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm text-[var(--color-text-secondary)]">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title or content" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm text-[var(--color-text-secondary)]">Visibility</label>
                    <select name="visibility" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All</option>
                        <option value="shared" @selected(($filters['visibility'] ?? '') === 'shared')>Shared</option>
                        <option value="private" @selected(($filters['visibility'] ?? '') === 'private')>Private</option>
                        <option value="locked" @selected(($filters['visibility'] ?? '') === 'locked')>Locked</option>
                    </select>
                </div>
                <div class="flex items-end justify-end gap-2">
                    <x-button type="submit" variant="primary" size="md">Apply</x-button>
                    <a href="{{ route('families.notes.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)] transition-colors">Reset</a>
                </div>
            </form>

            @if($notes->count() > 0)
                <div class="mt-6 space-y-3">
                    @foreach($notes as $note)
                        <a href="{{ route('families.notes.show', ['family' => $family->id, 'note' => $note->id]) }}" class="block bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $note->title }}</h3>
                                        <span class="text-xs px-2 py-1 rounded-full
                                            @if($note->visibility === 'shared') bg-green-100 text-green-800
                                            @elseif($note->visibility === 'private') bg-blue-100 text-blue-800
                                            @else bg-amber-100 text-amber-800
                                            @endif">
                                            {{ ucfirst($note->visibility) }}
                                        </span>
                                    </div>
                                    @if($note->visibility === 'shared' && $note->body)
                                        <p class="text-sm text-[var(--color-text-secondary)] line-clamp-2">{{ $note->body }}</p>
                                    @elseif($note->visibility === 'private')
                                        <p class="text-sm text-[var(--color-text-secondary)] italic">Private note - content hidden</p>
                                    @elseif($note->visibility === 'locked')
                                        <p class="text-sm text-[var(--color-text-secondary)] italic">Locked note - PIN required to view</p>
                                    @endif
                                    <div class="text-xs text-[var(--color-text-secondary)] mt-2 flex gap-3">
                                        <span>Updated: {{ $note->updated_at->format('M d, Y') }}</span>
                                        @if($note->creator)
                                            <span>By {{ $note->creator->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-[var(--color-text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $notes->links() }}
                </div>
            @else
                <div class="mt-6 text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No notes found.</p>
                    @can('create', \App\Models\Note::class)
                        <a href="{{ route('families.notes.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Create your first note</x-button>
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

