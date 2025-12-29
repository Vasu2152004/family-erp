<x-app-layout title="Create Investment: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Investments', 'url' => route('investments.index', ['family_id' => $family->id])],
            ['label' => 'Create Investment']
        ]" />

        <div class="card">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Create Investment</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Add a new investment for {{ $family->name }}
                </p>
            </div>

            <x-form method="POST" action="{{ route('investments.store', ['family_id' => $family->id]) }}" class="space-y-6">
                @csrf
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                @include('investments._form', ['investment' => null, 'members' => $members])

                <div class="flex gap-4">
                    <x-button type="submit" variant="primary" size="md">
                        Create Investment
                    </x-button>
                    <a href="{{ route('investments.index', ['family_id' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">
                            Cancel
                        </x-button>
                    </a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>









