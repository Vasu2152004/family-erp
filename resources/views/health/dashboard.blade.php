<x-app-layout title="Health Dashboard">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Families', 'url' => route('families.index')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health']
        ]" />

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('families.health.records.index', ['family' => $family->id]) }}" class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-[var(--color-text-primary)] mb-1">{{ $totalRecords }}</h3>
                <p class="text-sm text-[var(--color-text-secondary)]">Medical Records</p>
            </a>

            <a href="{{ route('families.health.visits.index', ['family' => $family->id]) }}" class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-[var(--color-text-primary)] mb-1">{{ $totalVisits }}</h3>
                <p class="text-sm text-[var(--color-text-secondary)]">Doctor Visits</p>
            </a>

            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-[var(--color-text-primary)] mb-1">{{ $activePrescriptions }}</h3>
                <p class="text-sm text-[var(--color-text-secondary)]">Active Prescriptions</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Visits -->
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-[var(--color-text-primary)]">Recent Visits</h2>
                    <a href="{{ route('families.health.visits.index', ['family' => $family->id]) }}" class="text-sm text-[var(--color-primary)] hover:underline">View All</a>
                </div>
                @forelse($recentVisits as $visit)
                    <div class="border-b border-[var(--color-border-primary)] pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $visit->doctor_name }}</h3>
                                <p class="text-sm text-[var(--color-text-secondary)]">{{ $visit->clinic_name }}</p>
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                    {{ $visit->visit_date->format('M d, Y') }}
                                    @if($visit->familyMember)
                                        • {{ $visit->familyMember->first_name }} {{ $visit->familyMember->last_name }}
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id]) }}">
                                <x-button variant="outline" size="sm">View</x-button>
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-[var(--color-text-secondary)] text-center py-4">No recent visits</p>
                @endforelse
            </div>

            <!-- Upcoming Visits -->
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-[var(--color-text-primary)]">Upcoming Visits</h2>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('families.health.visits.index', ['family' => $family->id, 'upcoming' => 1]) }}" class="text-sm text-[var(--color-primary)] hover:underline">View All</a>
                        <a href="{{ route('families.health.visits.create', ['family' => $family->id]) }}" class="text-sm text-[var(--color-primary)] hover:underline">Add Visit</a>
                    </div>
                </div>
                @forelse($upcomingVisits as $visit)
                    <div class="border-b border-[var(--color-border-primary)] pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $visit->doctor_name }}</h3>
                                <p class="text-sm text-[var(--color-text-secondary)]">{{ $visit->clinic_name }}</p>
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                    {{ $visit->visit_date->format('M d, Y') }}
                                    @if($visit->familyMember)
                                        • {{ $visit->familyMember->first_name }} {{ $visit->familyMember->last_name }}
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id]) }}">
                                <x-button variant="outline" size="sm">View</x-button>
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-[var(--color-text-secondary)] text-center py-4">No upcoming visits</p>
                @endforelse
            </div>
        </div>

        <!-- Active Prescriptions -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-[var(--color-text-primary)]">Active Prescriptions</h2>
                <a href="{{ route('families.health.visits.index', ['family' => $family->id]) }}" class="text-sm text-[var(--color-primary)] hover:underline">View All</a>
            </div>
            @forelse($activePrescriptionsList as $prescription)
                <div class="border-b border-[var(--color-border-primary)] pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $prescription->medication_name }}</h3>
                            <p class="text-sm text-[var(--color-text-secondary)]">{{ $prescription->dosage }} • {{ $prescription->frequency }}</p>
                            <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                @if($prescription->familyMember)
                                    {{ $prescription->familyMember->first_name }} {{ $prescription->familyMember->last_name }} •
                                @endif
                                {{ $prescription->start_date->format('M d, Y') }}
                                @if($prescription->end_date)
                                    - {{ $prescription->end_date->format('M d, Y') }}
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('families.health.visits.show', ['family' => $family->id, 'visit' => $prescription->doctorVisit->id]) }}">
                            <x-button variant="outline" size="sm">View</x-button>
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-[var(--color-text-secondary)] text-center py-4">No active prescriptions</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
