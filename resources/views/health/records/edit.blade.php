<x-app-layout title="Edit Medical Record">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', $family)],
            ['label' => 'Medical Records', 'url' => route('families.health.records.index', $family)],
            ['label' => 'Edit'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-[var(--color-text-primary)]">Update Record</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">Keep your health records current and complete.</p>
                </div>
                <a href="{{ route('families.health.records.index', $family) }}" class="text-sm text-[var(--color-primary)] hover:underline">Back</a>
            </div>

            <form method="POST" action="{{ route('families.health.records.update', [$family, $record]) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                @include('health.records._form', ['record' => $record])
                <div class="flex justify-end gap-3">
                    <a href="{{ route('families.health.records.index', $family) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)]">Cancel</a>
                    <x-button type="submit" variant="primary" size="md">Update Record</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

