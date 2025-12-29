<x-app-layout title="Edit Note">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Notes', 'url' => route('families.notes.index', ['family' => $family->id])],
            ['label' => $note->title, 'url' => route('families.notes.show', ['family' => $family->id, 'note' => $note->id])],
            ['label' => 'Edit'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Edit Note</h2>

            <x-form method="PATCH" action="{{ route('families.notes.update', ['family' => $family->id, 'note' => $note->id]) }}" class="space-y-6">
                @include('notes._form', ['note' => $note])

                <div class="flex gap-4 justify-end">
                    <a href="{{ route('families.notes.show', ['family' => $family->id, 'note' => $note->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                    <x-button type="submit" variant="primary" size="md">Update</x-button>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>

