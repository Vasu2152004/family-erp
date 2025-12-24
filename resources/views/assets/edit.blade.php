<x-app-layout title="Edit Asset: {{ $asset->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Assets', 'url' => route('assets.index', ['family_id' => $family->id])],
            ['label' => $asset->name, 'url' => route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id])],
            ['label' => 'Edit']
        ]" />

        <div class="card">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Edit Asset</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Update asset details
                </p>
            </div>

            <form method="POST" action="{{ route('assets.update', ['asset' => $asset->id, 'family_id' => $family->id]) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                @include('assets._form', ['asset' => $asset, 'members' => $members])

                <div class="flex flex-wrap gap-4 items-center">
                    <x-button type="submit" variant="primary" size="md">
                        Update Asset
                    </x-button>
                    <a href="{{ route('assets.show', ['asset' => $asset->id, 'family_id' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">
                            Cancel
                        </x-button>
                    </a>
                    <form method="POST" action="{{ route('assets.destroy', ['asset' => $asset->id, 'family_id' => $family->id]) }}" onsubmit="return confirm('Delete this asset?');">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="outline" size="md" class="text-red-600 border-red-300 hover:bg-red-50">
                            Delete
                        </x-button>
                    </form>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>






