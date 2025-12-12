<x-app-layout title="Task History">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Tasks', 'url' => route('families.tasks.index', ['family' => $family->id])],
            ['label' => $task->title, 'url' => route('families.tasks.show', ['family' => $family->id, 'task' => $task->id])],
            ['label' => 'History'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Task History</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">{{ $task->title }}</p>
                </div>
                <a href="{{ route('families.tasks.show', ['family' => $family->id, 'task' => $task->id]) }}">
                    <x-button variant="ghost" size="md">Back to Task</x-button>
                </a>
            </div>

            @if($logs->count() > 0)
                <div class="space-y-4">
                    @foreach($logs as $log)
                        <div class="border-l-4 
                            @if($log->status_to === 'pending') border-yellow-400
                            @elseif($log->status_to === 'in_progress') border-blue-400
                            @else border-green-400
                            @endif pl-4 py-3 bg-[var(--color-bg-secondary)] rounded-r-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-[var(--color-text-primary)]">
                                        @if($log->status_from && $log->status_from !== $log->status_to)
                                            Status changed from <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $log->status_from)) }}</span> to <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $log->status_to)) }}</span>
                                        @else
                                            Status: <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $log->status_to)) }}</span>
                                        @endif
                                    </p>
                                    @if($log->notes)
                                        <p class="text-sm text-[var(--color-text-secondary)] mt-2 whitespace-pre-wrap">{{ $log->notes }}</p>
                                    @endif
                                </div>
                                <div class="text-xs text-[var(--color-text-secondary)] ml-4 text-right">
                                    <div>{{ $log->created_at->format('M d, Y') }}</div>
                                    <div>{{ $log->created_at->format('h:i A') }}</div>
                                    @if($log->changedBy)
                                        <div class="mt-1">{{ $log->changedBy->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)]">No history available for this task.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

