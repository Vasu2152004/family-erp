<x-app-layout title="Edit Investment: {{ $investment->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Investments', 'url' => route('investments.index', ['family_id' => $family->id])],
            ['label' => $investment->name, 'url' => route('investments.show', ['investment' => $investment->id, 'family_id' => $family->id])],
            ['label' => 'Edit']
        ]" />

        <div class="card">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Edit Investment</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Update investment details
                </p>
            </div>

            <x-form method="POST" action="{{ route('investments.update', ['investment' => $investment->id, 'family_id' => $family->id]) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                @include('investments._form', ['investment' => $investment, 'members' => $members])

                <div class="flex flex-wrap gap-4 items-center">
                    <x-button type="submit" variant="primary" size="md">
                        Update Investment
                    </x-button>
                    <a href="{{ route('investments.show', ['investment' => $investment->id, 'family_id' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">
                            Cancel
                        </x-button>
                    </a>
                </div>
            </x-form>

            <div class="mt-4 pt-4 border-t border-[var(--color-border-primary)]">
                <x-form method="POST" action="{{ route('investments.destroy', ['investment' => $investment->id, 'family_id' => $family->id]) }}" onsubmit="return confirm('Are you sure you want to delete this investment? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="family_id" value="{{ $family->id }}">
                    <x-button type="submit" variant="outline" size="md" class="text-red-600 border-red-300 hover:bg-red-50">
                        Delete Investment
                    </x-button>
                </x-form>
            </div>
        </div>
    </div>
</x-app-layout>

