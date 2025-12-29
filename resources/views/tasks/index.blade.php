<x-app-layout title="Tasks">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Tasks'],
        ]" />

        <div class="card card-contrast">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="pill mb-2 w-fit">Tasks overview</p>
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Tasks</h2>
                        <p class="text-sm text-[var(--color-text-secondary)]">Manage household tasks and chores.</p>
                    </div>
                    @can('create', \App\Models\Task::class)
                        <a href="{{ route('families.tasks.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Create Task</x-button>
                        </a>
                    @endcan
                </div>

                <form method="GET" action="{{ route('families.tasks.index', ['family' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-[var(--color-surface)] border border-[var(--color-border-primary)] rounded-xl p-4 shadow-sm">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Task title..." class="rounded-xl border border-[var(--color-border-primary)] bg-[var(--color-surface-alt)] px-3 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Status</label>
                        <select name="status" class="rounded-xl border border-[var(--color-border-primary)] bg-[var(--color-surface-alt)] px-3 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All statuses</option>
                            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                            <option value="in_progress" @selected(($filters['status'] ?? '') === 'in_progress')>In Progress</option>
                            <option value="done" @selected(($filters['status'] ?? '') === 'done')>Done</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Frequency</label>
                        <select name="frequency" class="rounded-xl border border-[var(--color-border-primary)] bg-[var(--color-surface-alt)] px-3 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All frequencies</option>
                            <option value="daily" @selected(($filters['frequency'] ?? '') === 'daily')>Daily</option>
                            <option value="weekly" @selected(($filters['frequency'] ?? '') === 'weekly')>Weekly</option>
                            <option value="monthly" @selected(($filters['frequency'] ?? '') === 'monthly')>Monthly</option>
                            <option value="once" @selected(($filters['frequency'] ?? '') === 'once')>Once</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Assigned To</label>
                        <select name="family_member_id" class="rounded-xl border border-[var(--color-border-primary)] bg-[var(--color-surface-alt)] px-3 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All members</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" @selected(($filters['family_member_id'] ?? '') == $member->id)>
                                    {{ $member->first_name }} {{ $member->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-4 flex flex-wrap gap-2 justify-end">
                        <x-button type="submit" variant="primary" size="md">Apply Filters</x-button>
                        <a href="{{ route('families.tasks.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-xl border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-surface-alt)] transition-colors">Reset</a>
                    </div>
                </x-form>
            </div>

            @if($tasks->count() > 0)
                <div class="mt-6 grid grid-cols-1 gap-4">
                    @foreach($tasks as $task)
                        <a href="{{ route('families.tasks.show', ['family' => $family->id, 'task' => $task->id]) }}" class="block bg-[var(--color-surface)] rounded-xl border border-[var(--color-border-primary)] p-5 hover:shadow-lift transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $task->title }}</h3>
                                        <span class="badge
                                            @if($task->status === 'pending') badge-warning
                                            @elseif($task->status === 'in_progress') badge-info
                                            @else badge-success
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                        <span class="badge bg-[var(--color-surface-alt)] text-[var(--color-text-secondary)] border-[var(--color-border-primary)]">
                                            {{ ucfirst($task->frequency) }}
                                        </span>
                                    </div>
                                    @if($task->description)
                                        <p class="text-sm text-[var(--color-text-secondary)] mb-2 line-clamp-2">{{ $task->description }}</p>
                                    @endif
                                    <div class="flex items-center gap-4 text-xs text-[var(--color-text-secondary)]">
                                        @if($task->familyMember)
                                            <span>{{ $task->familyMember->first_name }} {{ $task->familyMember->last_name }}</span>
                                        @else
                                            <span class="text-gray-400">Unassigned</span>
                                        @endif
                                        @if($task->due_date)
                                            <span>Due: {{ $task->due_date->format('M d, Y') }}</span>
                                        @endif
                                        <span>Created: {{ $task->created_at->format('M d, Y') }}</span>
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
                    {{ $tasks->links() }}
                </div>
            @else
                <div class="mt-6 text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No tasks found.</p>
                    @can('create', \App\Models\Task::class)
                        <a href="{{ route('families.tasks.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Create Your First Task</x-button>
                        </a>
                    @endcan
                </div>
            @endif

            <!-- Task Status Distribution Chart -->
            @if(count($taskStatusData ?? []) > 0 && array_sum(array_column($taskStatusData, 'count')) > 0)
                <div class="mt-6 card">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Task Status Distribution</h2>
                    <div id="taskStatusChart" style="min-height: 400px;"></div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
        <script src="{{ asset('js/task-charts.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Task Status Data
                const taskStatusData = @json($taskStatusData ?? []);
                
                // Initialize charts once ApexCharts is loaded
                if (typeof ApexCharts !== 'undefined' && typeof initTaskCharts === 'function') {
                    initTaskCharts(taskStatusData);
                } else {
                    // Wait for ApexCharts to load
                    window.addEventListener('load', function() {
                        if (typeof ApexCharts !== 'undefined' && typeof initTaskCharts === 'function') {
                            initTaskCharts(taskStatusData);
                        } else {
                            console.error('ApexCharts or initTaskCharts function not available');
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>




