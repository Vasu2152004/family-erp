<x-app-layout title="Note Details">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Notes', 'url' => route('families.notes.index', ['family' => $family->id])],
            ['label' => $note->title],
        ]" />

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $note->title }}</h2>
                    <span class="text-xs px-2 py-1 rounded-full
                        @if($note->visibility === 'shared') bg-green-100 text-green-800
                        @elseif($note->visibility === 'private') bg-blue-100 text-blue-800
                        @else bg-amber-100 text-amber-800
                        @endif">
                        {{ ucfirst($note->visibility) }}
                    </span>
                </div>
                <div class="flex gap-2">
                    @can('update', $note)
                        <a href="{{ route('families.notes.edit', ['family' => $family->id, 'note' => $note->id]) }}">
                            <x-button variant="outline" size="md">Edit</x-button>
                        </a>
                    @endcan
                    @can('delete', $note)
                        <x-form method="DELETE" action="{{ route('families.notes.destroy', ['family' => $family->id, 'note' => $note->id]) }}" onsubmit="return confirm('Delete this note?');">
                            <x-button variant="danger" size="md">Delete</x-button>
                        </x-form>
                    @endcan
                    <a href="{{ route('families.notes.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Back</x-button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-[var(--color-text-secondary)] mb-4">
                <div>Created: {{ $note->created_at->format('M d, Y h:i A') }}</div>
                <div>Updated: {{ $note->updated_at->format('M d, Y h:i A') }}</div>
                @if($note->creator)
                    <div>By: {{ $note->creator->name }}</div>
                @endif
                @if($note->updater)
                    <div>Last updated by: {{ $note->updater->name }}</div>
                @endif
            </div>

            @if($note->visibility === 'locked' && !$isUnlocked)
                <div class="bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-xl p-6">
                    <p class="text-[var(--color-text-primary)] font-semibold mb-3">This note is locked. Enter PIN to unlock.</p>
                    <x-form method="POST" action="{{ route('families.notes.unlock', ['family' => $family->id, 'note' => $note->id]) }}" class="flex flex-col gap-3 max-w-md">
                        <x-input type="password" name="pin" placeholder="Enter PIN" required autocomplete="current-password" />
                        <x-error-message field="pin" />
                        <div class="flex gap-3">
                            <x-button type="submit" variant="primary" size="md">Unlock</x-button>
                            <a href="{{ route('families.notes.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">Back</a>
                        </div>
                    </x-form>
                </div>
            @else
                <div class="prose prose-sm max-w-none text-[var(--color-text-primary)]">
                    @if($note->body)
                        <p class="whitespace-pre-wrap">{{ $note->body }}</p>
                    @else
                        <p class="text-[var(--color-text-secondary)]">No content.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

