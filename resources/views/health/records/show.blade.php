<x-app-layout title="Medical Record">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', ['family' => $family->id])],
            ['label' => 'Medical Records', 'url' => route('families.health.records.index', ['family' => $family->id])],
            ['label' => 'View Record'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Medical Record</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">{{ $record->created_at->format('M d, Y') }}</p>
                </div>
                <div class="flex gap-2">
                    @can('update', $record)
                        <a href="{{ route('families.health.records.edit', ['family' => $family->id, 'record' => $record->id]) }}">
                            <x-button variant="outline" size="md">Edit</x-button>
                        </a>
                    @endcan
                    <a href="{{ route('families.health.records.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Back</x-button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm text-[var(--color-text-secondary)]">Title</label>
                    <p class="text-[var(--color-text-primary)] font-medium">{{ $record->title }}</p>
                </div>

                <div>
                    <label class="text-sm text-[var(--color-text-secondary)]">Record Type</label>
                    <p class="text-[var(--color-text-primary)] font-medium">{{ ucfirst($record->record_type) }}</p>
                </div>

                @if($record->familyMember)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Linked Member</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $record->familyMember->first_name }} {{ $record->familyMember->last_name }}</p>
                    </div>
                @endif

                @if($record->doctor_name)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Doctor Name</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $record->doctor_name }}</p>
                    </div>
                @endif

                @if($record->category)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Category</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $record->category }}</p>
                    </div>
                @endif

                @if($record->primary_condition)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Primary Condition</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $record->primary_condition }}</p>
                    </div>
                @endif

                @if($record->severity)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Severity</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ ucfirst($record->severity) }}</p>
                    </div>
                @endif

                @if($record->follow_up_at)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Follow-up Date</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $record->follow_up_at->format('M d, Y') }}</p>
                    </div>
                @endif
            </div>

            @if($record->symptoms)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Symptoms</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $record->symptoms }}</p>
                </div>
            @endif

            @if($record->diagnosis)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Diagnosis</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $record->diagnosis }}</p>
                </div>
            @endif

            @if($record->treatment_plan)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Treatment Plan</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $record->treatment_plan }}</p>
                </div>
            @endif

                @if($record->summary)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Summary</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $record->summary }}</p>
                </div>
            @endif

            @if($record->notes)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Notes</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $record->notes }}</p>
                </div>
            @endif

            @if($record->doctorVisits->count() > 0)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)] mb-2 block">Related Doctor Visits</label>
                    <div class="space-y-2">
                        @foreach($record->doctorVisits as $visit)
                            <a href="{{ route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id]) }}" class="block p-3 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] hover:shadow-md transition-shadow">
                                <p class="font-medium text-[var(--color-text-primary)]">{{ $visit->doctor_name }}</p>
                                <p class="text-sm text-[var(--color-text-secondary)]">{{ $visit->visit_date->format('M d, Y') }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

