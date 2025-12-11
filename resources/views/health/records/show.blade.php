<x-app-layout title="Record Details">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', $family)],
            ['label' => 'Medical Records', 'url' => route('families.health.records.index', $family)],
            ['label' => 'Details'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-[var(--color-text-secondary)]">{{ ucfirst($record->record_type) }}</p>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $record->title }}</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        {{ $record->familyMember?->first_name }} {{ $record->familyMember?->last_name }}
                        @if($record->recorded_at) • {{ $record->recorded_at->format('M d, Y') }} @endif
                        @if($record->doctor_name) • Dr. {{ $record->doctor_name }} @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('families.health.records.edit', [$family, $record]) }}" class="px-3 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] text-sm hover:bg-[var(--color-bg-secondary)]">Edit</a>
                    <form method="POST" action="{{ route('families.health.records.destroy', [$family, $record]) }}" onsubmit="return confirm('Delete this record?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 rounded-lg border border-red-200 text-red-600 text-sm hover:bg-red-50">Delete</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($record->primary_condition)
                    <div class="p-3 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                        <p class="text-xs text-[var(--color-text-secondary)]">Primary Condition</p>
                        <p class="font-semibold text-[var(--color-text-primary)]">{{ $record->primary_condition }}</p>
                    </div>
                @endif
                @if($record->severity)
                    <div class="p-3 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                        <p class="text-xs text-[var(--color-text-secondary)]">Severity</p>
                        <p class="font-semibold text-[var(--color-text-primary)] capitalize">{{ $record->severity }}</p>
                    </div>
                @endif
                @if($record->follow_up_at)
                    <div class="p-3 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                        <p class="text-xs text-[var(--color-text-secondary)]">Follow-up</p>
                        <p class="font-semibold text-[var(--color-text-primary)]">{{ $record->follow_up_at->format('M d, Y') }}</p>
                    </div>
                @endif
            </div>

            @if($record->symptoms)
                <div class="mt-4">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-1">Symptoms</p>
                    <p class="text-sm text-[var(--color-text-secondary)] whitespace-pre-line">{{ $record->symptoms }}</p>
                </div>
            @endif

            @if($record->diagnosis)
                <div class="mt-4">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-1">Diagnosis</p>
                    <p class="text-sm text-[var(--color-text-secondary)] whitespace-pre-line">{{ $record->diagnosis }}</p>
                </div>
            @endif

            @if($record->treatment_plan)
                <div class="mt-4">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-1">Treatment Plan</p>
                    <p class="text-sm text-[var(--color-text-secondary)] whitespace-pre-line">{{ $record->treatment_plan }}</p>
                </div>
            @endif

            @if($record->summary)
                <div class="mt-4">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-1">Summary</p>
                    <p class="text-sm text-[var(--color-text-secondary)] whitespace-pre-line">{{ $record->summary }}</p>
                </div>
            @endif

            @if($record->notes)
                <div class="mt-4">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-1">Notes</p>
                    <p class="text-sm text-[var(--color-text-secondary)] whitespace-pre-line">{{ $record->notes }}</p>
                </div>
            @endif
        </div>

        <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Recent Visits (linked)</h3>
                    <p class="text-sm text-[var(--color-text-secondary)]">Last 10 visits attached to this record.</p>
                </div>
                <a href="{{ route('families.health.visits.index', $family) }}" class="text-sm text-[var(--color-primary)] hover:underline">All visits</a>
            </div>
            <div class="space-y-3">
                @forelse($record->visits as $visit)
                    <a href="{{ route('families.health.visits.show', [$family, $visit]) }}" class="block p-3 rounded-lg border border-[var(--color-border-primary)] hover:border-[var(--color-primary)] bg-[var(--color-bg-secondary)] transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-[var(--color-text-primary)]">{{ $visit->doctor_name ?? 'Doctor visit' }}</p>
                                <p class="text-xs text-[var(--color-text-secondary)]">
                                    {{ $visit->visit_date?->format('M d, Y') }} @if($visit->diagnosis) • {{ $visit->diagnosis }} @endif
                                </p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full
                                @class([
                                    'bg-emerald-100 text-emerald-700' => $visit->status === 'completed',
                                    'bg-amber-100 text-amber-700' => $visit->status === 'scheduled',
                                    'bg-rose-100 text-rose-700' => $visit->status === 'cancelled',
                                ])">
                                {{ ucfirst($visit->status) }}
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-[var(--color-text-secondary)]">No linked visits yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

