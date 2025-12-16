<x-app-layout title="Edit Task">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Tasks', 'url' => route('families.tasks.index', ['family' => $family->id])],
            ['label' => $task->title, 'url' => route('families.tasks.show', ['family' => $family->id, 'task' => $task->id])],
            ['label' => 'Edit'],
        ]" />

        <div class="card card-contrast">
            <p class="pill mb-3 w-fit">Edit Task</p>
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Edit Task</h2>

            <form method="POST" action="{{ route('families.tasks.update', ['family' => $family->id, 'task' => $task->id]) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('tasks._form', ['task' => $task])

                <div class="flex gap-4 justify-end">
                    <a href="{{ route('families.tasks.show', ['family' => $family->id, 'task' => $task->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                    <x-button type="submit" variant="primary" size="md">Update Task</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>




