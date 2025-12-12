<x-app-layout title="Create Task">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Tasks', 'url' => route('families.tasks.index', ['family' => $family->id])],
            ['label' => 'Create'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Create Task</h2>

            <form method="POST" action="{{ route('families.tasks.store', ['family' => $family->id]) }}" class="space-y-6">
                @csrf

                @include('tasks._form', ['task' => null])

                <div class="flex gap-4 justify-end">
                    <a href="{{ route('families.tasks.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                    <x-button type="submit" variant="primary" size="md">Create Task</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

