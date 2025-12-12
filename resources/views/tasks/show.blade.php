<x-app-layout title="Task Details">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Tasks', 'url' => route('families.tasks.index', ['family' => $family->id])],
            ['label' => $task->title],
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

        <!-- Task Details -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $task->title }}</h2>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs px-2 py-1 rounded-full 
                            @if($task->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($task->status === 'in_progress') bg-blue-100 text-blue-800
                            @else bg-green-100 text-green-800
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                        </span>
                        <span class="text-xs px-2 py-1 rounded-full bg-[var(--color-bg-secondary)] text-[var(--color-text-secondary)]">
                            {{ ucfirst($task->frequency) }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    @can('update', $task)
                        <a href="{{ route('families.tasks.edit', ['family' => $family->id, 'task' => $task->id]) }}">
                            <x-button variant="outline" size="md">Edit</x-button>
                        </a>
                    @endcan
                    <a href="{{ route('families.tasks.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Back</x-button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($task->familyMember)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Assigned To</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $task->familyMember->first_name }} {{ $task->familyMember->last_name }}</p>
                    </div>
                @else
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Assigned To</label>
                        <p class="text-[var(--color-text-primary)] font-medium text-gray-400">Unassigned</p>
                    </div>
                @endif

                @if($task->due_date)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Due Date</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $task->due_date->format('M d, Y') }}</p>
                    </div>
                @endif

                @if($task->recurrence_day)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Recurrence Day</label>
                        <p class="text-[var(--color-text-primary)] font-medium">
                            @if($task->frequency === 'weekly')
                                {{ ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$task->recurrence_day - 1] }}
                            @else
                                Day {{ $task->recurrence_day }} of month
                            @endif
                        </p>
                    </div>
                @endif

                @if($task->recurrence_time)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Recurrence Time</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ \Carbon\Carbon::parse($task->recurrence_time)->format('h:i A') }}</p>
                    </div>
                @endif

                <div>
                    <label class="text-sm text-[var(--color-text-secondary)]">Created</label>
                    <p class="text-[var(--color-text-primary)] font-medium">{{ $task->created_at->format('M d, Y') }}</p>
                </div>

                @if($task->createdBy)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Created By</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $task->createdBy->name }}</p>
                    </div>
                @endif
            </div>

            @if($task->description)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Description</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $task->description }}</p>
                </div>
            @endif

            <!-- Status Update Section -->
            @if(auth()->user()->can('updateStatus', $task))
                <div class="mt-8 pt-6 border-t border-[var(--color-border-primary)]">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Update Status</h3>
                    <form method="POST" action="{{ route('families.tasks.update-status', ['family' => $family->id, 'task' => $task->id]) }}" class="space-y-4" id="task-status-form">
                        @csrf
                        <input type="hidden" name="_method" value="PATCH">
                        <div class="flex gap-3">
                            @if($task->status !== 'pending' && $task->canTransitionTo('pending'))
                                <button type="submit" name="status" value="pending" class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition-colors">
                                    Mark as Pending
                                </button>
                            @endif
                            @if($task->status !== 'in_progress' && $task->canTransitionTo('in_progress'))
                                <button type="submit" name="status" value="in_progress" class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 transition-colors">
                                    Start Task
                                </button>
                            @endif
                            @if($task->status !== 'done' && $task->canTransitionTo('done'))
                                <button type="submit" name="status" value="done" class="px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                                    Mark as Done
                                </button>
                            @endif
                        </div>
                        <div>
                            <label class="text-sm text-[var(--color-text-secondary)]">Notes (Optional)</label>
                            <textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Add notes about this status change..."></textarea>
                        </div>
                    </form>
                </div>
            @endcan
        </div>

        <!-- Task Logs -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Task History</h3>
                <a href="{{ route('families.tasks.logs.index', ['family' => $family->id, 'task' => $task->id]) }}" class="text-sm text-[var(--color-primary)] hover:underline">
                    View All Logs
                </a>
            </div>

            @if($task->logs->count() > 0)
                <div class="space-y-4">
                    @foreach($task->logs->take(3) as $log)
                        <div class="border-l-4 
                            @if($log->status_to === 'pending') border-yellow-400
                            @elseif($log->status_to === 'in_progress') border-blue-400
                            @else border-green-400
                            @endif pl-4 py-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-[var(--color-text-primary)]">
                                        @if($log->status_from && $log->status_from !== $log->status_to)
                                            Status changed from <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $log->status_from)) }}</span> to <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $log->status_to)) }}</span>
                                        @else
                                            Status: <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $log->status_to)) }}</span>
                                        @endif
                                    </p>
                                    @if($log->notes)
                                        <p class="text-xs text-[var(--color-text-secondary)] mt-1">{{ $log->notes }}</p>
                                    @endif
                                </div>
                                <div class="text-xs text-[var(--color-text-secondary)]">
                                    {{ $log->created_at->format('M d, Y h:i A') }}
                                    @if($log->changedBy)
                                        • {{ $log->changedBy->name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-[var(--color-text-secondary)] text-center py-4">No history available</p>
            @endif
        </div>
    </div>
</x-app-layout>

