<x-app-layout title="Medical Records">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', $family)],
            ['label' => 'Medical Records'],
        ]" />

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Medical Records</h2>
                <p class="text-sm text-[var(--color-text-secondary)]">Track diagnoses, lab reports, and treatments for each family member.</p>
            </div>
            <a href="{{ route('families.health.records.create', $family) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold shadow hover:shadow-lg transition-all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"></path>
                </svg>
                Add Record
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($records as $record)
                <div class="p-4 rounded-xl border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-[var(--color-text-secondary)]">{{ ucfirst($record->record_type) }}</p>
                            <a href="{{ route('families.health.records.show', [$family, $record]) }}" class="text-lg font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-primary)]">
                                {{ $record->title }}
                            </a>
                            <p class="text-sm text-[var(--color-text-secondary)]">
                                {{ $record->familyMember?->first_name }} {{ $record->familyMember?->last_name }}
                                @if($record->recorded_at)
                                    • {{ $record->recorded_at->format('M d, Y') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('families.health.records.show', [$family, $record]) }}" class="px-3 py-1 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] text-xs hover:border-[var(--color-primary)]">View</a>
                            <a href="{{ route('families.health.records.edit', [$family, $record]) }}" class="px-3 py-1 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] text-xs hover:border-[var(--color-primary)]">Edit</a>
                            <form method="POST" action="{{ route('families.health.records.destroy', [$family, $record]) }}" onsubmit="return confirm('Delete this record?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 rounded-lg border border-red-200 text-red-600 text-xs hover:bg-red-50">Delete</button>
                            </form>
                        </div>
                    </div>
                    @if($record->summary)
                        <p class="text-sm text-[var(--color-text-secondary)] mb-2">{{ $record->summary }}</p>
                    @endif
                    @if($record->notes)
                        <p class="text-xs text-[var(--color-text-secondary)] line-clamp-2">{{ $record->notes }}</p>
                    @endif
                </div>
            @empty
                <div class="col-span-2">
                    <div class="p-6 rounded-xl border border-dashed border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] text-center">
                        <p class="text-[var(--color-text-secondary)]">No medical records yet. Start by adding one.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div>
            {{ $records->links() }}
        </div>
    </div>
</x-app-layout>

