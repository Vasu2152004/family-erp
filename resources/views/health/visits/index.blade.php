<x-app-layout title="Doctor Visits">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', $family)],
            ['label' => 'Doctor Visits'],
        ]" />

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Doctor Visits</h2>
                <p class="text-sm text-[var(--color-text-secondary)]">Log appointments, diagnoses, and follow-ups.</p>
            </div>
            <a href="{{ route('families.health.visits.create', $family) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold shadow hover:shadow-lg transition-all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"></path>
                </svg>
                Schedule Visit
            </a>
        </div>

        <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-[var(--color-text-secondary)] mb-1 block">Member</label>
                    <select name="family_member_id" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)]">
                        <option value="">All</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" @selected(($filters['family_member_id'] ?? null) == $member->id)>
                                {{ $member->first_name }} {{ $member->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm text-[var(--color-text-secondary)] mb-1 block">Status</label>
                    <select name="status" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)]">
                        <option value="">All</option>
                        <option value="scheduled" @selected(($filters['status'] ?? null) === 'scheduled')>Scheduled</option>
                        <option value="completed" @selected(($filters['status'] ?? null) === 'completed')>Completed</option>
                        <option value="cancelled" @selected(($filters['status'] ?? null) === 'cancelled')>Cancelled</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[var(--color-primary)] text-white text-sm shadow hover:shadow-md">Filter</button>
                    <a href="{{ route('families.health.visits.index', $family) }}" class="px-3 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] text-sm hover:bg-[var(--color-bg-secondary)]">Reset</a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($visits as $visit)
                <a href="{{ route('families.health.visits.show', [$family, $visit]) }}" class="p-4 rounded-xl border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] shadow-sm hover:border-[var(--color-primary)] transition-colors">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-[var(--color-text-secondary)]">{{ $visit->doctor_name ?? 'Doctor visit' }}</p>
                            <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $visit->reason ?? 'Consultation' }}</h3>
                            <p class="text-sm text-[var(--color-text-secondary)]">
                                {{ $visit->familyMember?->first_name }} {{ $visit->familyMember?->last_name }} • {{ $visit->visit_date?->format('M d, Y') }}
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
                    @if($visit->diagnosis)
                        <p class="text-sm text-[var(--color-text-secondary)] mt-2 line-clamp-2">Diagnosis: {{ $visit->diagnosis }}</p>
                    @endif
                </a>
            @empty
                <div class="col-span-2">
                    <div class="p-6 rounded-xl border border-dashed border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] text-center">
                        <p class="text-[var(--color-text-secondary)]">No visits logged yet.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div>
            {{ $visits->links() }}
        </div>
    </div>
</x-app-layout>

