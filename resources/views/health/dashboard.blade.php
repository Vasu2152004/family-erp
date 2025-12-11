<x-app-layout title="Health Center">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health'],
        ]" />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--color-text-secondary)]">Medical Records</p>
                        <p class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $recordCounts->sum() }}</p>
                    </div>
                    <a href="{{ route('families.health.records.index', $family) }}" class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">
                        Records
                    </a>
                </div>
                <div class="mt-4 space-y-2">
                    @foreach($recordCounts as $type => $count)
                        <div class="flex items-center justify-between text-sm text-[var(--color-text-secondary)]">
                            <span class="capitalize">{{ str_replace('_', ' ', $type) }}</span>
                            <span class="font-semibold text-[var(--color-text-primary)]">{{ $count }}</span>
                        </div>
                        <div class="w-full h-2 bg-[var(--color-bg-secondary)] rounded-full overflow-hidden">
                            <div class="h-2 bg-gradient-to-r from-blue-500 to-indigo-500" style="width: {{ $recordCounts->sum() > 0 ? ($count / max(1, $recordCounts->sum())) * 100 : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--color-text-secondary)]">Visits</p>
                        <p class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $visitStats->sum() }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('families.health.visits.create', $family) }}" class="px-3 py-1 text-xs rounded-full bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">Add Visit</a>
                        <span class="px-3 py-1 text-xs rounded-full bg-emerald-100 text-emerald-700">Doctor</span>
                    </div>
                </div>
                <div class="mt-4">
                    <canvas id="healthVisitChart"
                        data-visit-stats='@json($visitStats)'
                        class="w-full h-40"></canvas>
                </div>
            </div>

            <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--color-text-secondary)]">Active Prescriptions</p>
                        <p class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $activePrescriptions->count() }}</p>
                    </div>
                    <a href="{{ route('families.health.visits.index', $family) }}" class="text-sm text-[var(--color-primary)] hover:underline">Manage</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($activePrescriptions as $prescription)
                        <div class="p-3 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $prescription->medication_name }}</p>
                                    <p class="text-xs text-[var(--color-text-secondary)]">
                                        {{ $prescription->familyMember?->first_name }} {{ $prescription->familyMember?->last_name }}
                                    </p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700">{{ $prescription->frequency ?? 'As advised' }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--color-text-secondary)]">No active prescriptions.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Recent Visits</h3>
                        <p class="text-sm text-[var(--color-text-secondary)]">Latest completed doctor visits</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('families.health.visits.create', $family) }}" class="text-sm text-[var(--color-primary)] hover:underline">Add visit</a>
                        <a href="{{ route('families.health.visits.index', $family) }}" class="text-sm text-[var(--color-primary)] hover:underline">Edit visits</a>
                    </div>
                </div>
                <div class="space-y-3">
                    @forelse($recentVisits as $visit)
                        <a href="{{ route('families.health.visits.show', [$family, $visit]) }}" class="block p-3 rounded-lg border border-[var(--color-border-primary)] hover:border-[var(--color-primary)] bg-[var(--color-bg-secondary)] transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $visit->doctor_name ?? 'Doctor visit' }}</p>
                                    <p class="text-xs text-[var(--color-text-secondary)]">
                                        {{ $visit->familyMember?->first_name }} {{ $visit->familyMember?->last_name }} • {{ $visit->visit_date?->format('M d, Y') }}
                                    </p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">
                                    Completed
                                </span>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-[var(--color-text-secondary)]">No completed visits yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Upcoming Visits</h3>
                        <p class="text-sm text-[var(--color-text-secondary)]">Scheduled visits ahead</p>
                    </div>
                    <a href="{{ route('families.health.visits.create', $family) }}" class="text-sm text-[var(--color-primary)] hover:underline">Add visit</a>
                </div>
                <div class="space-y-3">
                    @forelse($upcomingVisits as $visit)
                        <a href="{{ route('families.health.visits.show', [$family, $visit]) }}" class="block p-3 rounded-lg border border-[var(--color-border-primary)] hover:border-[var(--color-primary)] bg-[var(--color-bg-secondary)] transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $visit->doctor_name ?? 'Doctor visit' }}</p>
                                    <p class="text-xs text-[var(--color-text-secondary)]">
                                        {{ $visit->familyMember?->first_name }} {{ $visit->familyMember?->last_name }} • {{ $visit->visit_date?->format('M d, Y') }}
                                    </p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700">
                                    Scheduled
                                </span>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-[var(--color-text-secondary)]">No upcoming visits.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</x-app-layout>

