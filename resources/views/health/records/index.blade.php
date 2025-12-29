<x-app-layout title="Medical Records">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', ['family' => $family->id])],
            ['label' => 'Medical Records'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Medical Records</h2>
                        <p class="text-sm text-[var(--color-text-secondary)]">Manage medical records and health information.</p>
                    </div>
                    @can('create', \App\Models\MedicalRecord::class)
                        <a href="{{ route('families.health.records.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Record</x-button>
                        </a>
                    @endcan
                </div>

                <x-form method="GET" action="{{ route('families.health.records.index', ['family' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-xl p-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Condition, category..." class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Record Type</label>
                        <select name="record_type" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All types</option>
                            <option value="general" @selected(($filters['record_type'] ?? '') === 'general')>General</option>
                            <option value="diagnosis" @selected(($filters['record_type'] ?? '') === 'diagnosis')>Diagnosis</option>
                            <option value="lab" @selected(($filters['record_type'] ?? '') === 'lab')>Lab</option>
                            <option value="imaging" @selected(($filters['record_type'] ?? '') === 'imaging')>Imaging</option>
                            <option value="vaccine" @selected(($filters['record_type'] ?? '') === 'vaccine')>Vaccine</option>
                            <option value="allergy" @selected(($filters['record_type'] ?? '') === 'allergy')>Allergy</option>
                            <option value="other" @selected(($filters['record_type'] ?? '') === 'other')>Other</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Member</label>
                        <select name="family_member_id" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All members</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" @selected(($filters['family_member_id'] ?? '') == $member->id)>
                                    {{ $member->first_name }} {{ $member->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3 flex flex-wrap gap-2 justify-end">
                        <x-button type="submit" variant="primary" size="md">Apply Filters</x-button>
                        <a href="{{ route('families.health.records.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)] transition-colors">Reset</a>
                    </div>
                </x-form>
            </div>

            @if($records->count() > 0)
                <div class="mt-6 grid grid-cols-1 gap-4">
                    @foreach($records as $record)
                        <a href="{{ route('families.health.records.show', ['family' => $family->id, 'record' => $record->id]) }}" class="block bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $record->title }}</h3>
                                        <span class="text-xs px-2 py-1 rounded-full bg-[var(--color-bg-primary)] text-[var(--color-text-secondary)]">{{ ucfirst($record->record_type) }}</span>
                                        @if($record->severity)
                                            <span class="text-xs px-2 py-1 rounded-full bg-[var(--color-bg-primary)] text-[var(--color-text-secondary)]">{{ ucfirst($record->severity) }}</span>
                                        @endif
                                    </div>
                                    @if($record->familyMember)
                                        <p class="text-sm text-[var(--color-text-secondary)] mb-1">{{ $record->familyMember->first_name }} {{ $record->familyMember->last_name }}</p>
                                    @endif
                                    @if($record->diagnosis)
                                        <p class="text-sm text-[var(--color-text-secondary)] line-clamp-2">{{ Str::limit($record->diagnosis, 100) }}</p>
                                    @endif
                                    <p class="text-xs text-[var(--color-text-secondary)] mt-2">{{ $record->created_at->format('M d, Y') }}</p>
                                </div>
                                <svg class="w-5 h-5 text-[var(--color-text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $records->links() }}
                </div>
            @else
                <div class="mt-6 text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No medical records found.</p>
                    @can('create', \App\Models\MedicalRecord::class)
                        <a href="{{ route('families.health.records.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Your First Record</x-button>
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

