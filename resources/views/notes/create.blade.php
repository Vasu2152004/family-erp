<x-app-layout title="Create Note">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Notes', 'url' => route('families.notes.index', ['family' => $family->id])],
            ['label' => 'Create'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Create Note</h2>

            <form method="POST" action="{{ route('families.notes.store', ['family' => $family->id]) }}" class="space-y-6">
                @csrf
                @include('notes._form')

                <div class="flex gap-4 justify-end">
                    <a href="{{ route('families.notes.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                    <x-button type="submit" variant="primary" size="md">Create</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

