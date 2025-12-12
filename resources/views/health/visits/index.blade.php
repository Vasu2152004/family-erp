<x-app-layout title="Doctor Visits">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', ['family' => $family->id])],
            ['label' => 'Doctor Visits'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">
                            @if(($filters['upcoming'] ?? false))
                                Upcoming Visits
                            @else
                                Doctor Visits
                            @endif
                        </h2>
                        <p class="text-sm text-[var(--color-text-secondary)]">
                            @if(($filters['upcoming'] ?? false))
                                View all scheduled upcoming doctor visits and appointments.
                            @else
                                Manage doctor visits and appointments.
                            @endif
                        </p>
                    </div>
                    @can('create', \App\Models\DoctorVisit::class)
                        <a href="{{ route('families.health.visits.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Visit</x-button>
                        </a>
                    @endcan
                </div>

                <form method="GET" action="{{ route('families.health.visits.index', ['family' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-xl p-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Doctor, clinic..." class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Visit Type</label>
                        <select name="visit_type" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All types</option>
                            <option value="consultation" @selected(($filters['visit_type'] ?? '') === 'consultation')>Consultation</option>
                            <option value="follow_up" @selected(($filters['visit_type'] ?? '') === 'follow_up')>Follow Up</option>
                            <option value="emergency" @selected(($filters['visit_type'] ?? '') === 'emergency')>Emergency</option>
                            <option value="routine_checkup" @selected(($filters['visit_type'] ?? '') === 'routine_checkup')>Routine Checkup</option>
                            <option value="surgery" @selected(($filters['visit_type'] ?? '') === 'surgery')>Surgery</option>
                            <option value="other" @selected(($filters['visit_type'] ?? '') === 'other')>Other</option>
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
                        <a href="{{ route('families.health.visits.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)] transition-colors">Reset</a>
                        @if(!($filters['upcoming'] ?? false))
                            <a href="{{ route('families.health.visits.index', ['family' => $family->id, 'upcoming' => 1]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-primary)] text-[var(--color-primary)] hover:bg-[var(--color-primary)] hover:text-white transition-colors">Show Upcoming Only</a>
                        @endif
                    </div>
                </form>
            </div>

            @if($visits->count() > 0)
                <div class="mt-6 grid grid-cols-1 gap-4">
                    @foreach($visits as $visit)
                        <a href="{{ route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id]) }}" class="block bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $visit->doctor_name }}</h3>
                                        <span class="text-xs px-2 py-1 rounded-full bg-[var(--color-bg-primary)] text-[var(--color-text-secondary)]">{{ ucfirst(str_replace('_', ' ', $visit->visit_type ?? 'consultation')) }}</span>
                                    </div>
                                    @if($visit->clinic_name)
                                        <p class="text-sm text-[var(--color-text-secondary)] mb-1">{{ $visit->clinic_name }}</p>
                                    @endif
                                    @if($visit->familyMember)
                                        <p class="text-sm text-[var(--color-text-secondary)] mb-1">{{ $visit->familyMember->first_name }} {{ $visit->familyMember->last_name }}</p>
                                    @endif
                                    <p class="text-xs text-[var(--color-text-secondary)]">
                                        {{ $visit->visit_date->format('M d, Y') }}
                                        @if($visit->visit_time)
                                            • {{ \Carbon\Carbon::parse($visit->visit_time)->format('h:i A') }}
                                        @endif
                                    </p>
                                </div>
                                <svg class="w-5 h-5 text-[var(--color-text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $visits->links() }}
                </div>
            @else
                <div class="mt-6 text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No doctor visits found.</p>
                    @can('create', \App\Models\DoctorVisit::class)
                        <a href="{{ route('families.health.visits.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Add Your First Visit</x-button>
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

