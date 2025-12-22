<x-app-layout title="Create Asset: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Assets', 'url' => route('assets.index', ['family_id' => $family->id])],
            ['label' => 'Create Asset']
        ]" />

        <div class="card">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Create Asset</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Add a new asset for {{ $family->name }}
                </p>
            </div>

            <form method="POST" action="{{ route('assets.store', ['family_id' => $family->id]) }}" class="space-y-6">
                @csrf
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                @include('assets._form', ['asset' => null, 'members' => $members])

                <div class="flex gap-4">
                    <x-button type="submit" variant="primary" size="md">
                        Create Asset
                    </x-button>
                    <a href="{{ route('assets.index', ['family_id' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">
                            Cancel
                        </x-button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>




