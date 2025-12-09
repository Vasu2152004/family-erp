<x-app-layout title="Edit Family">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Edit Family']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Edit Family</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Update family information
                </p>
            </div>

        <form method="POST" action="{{ route('families.update', $family) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <x-label for="name" required>Family Name</x-label>
                <x-input 
                    type="text" 
                    name="name" 
                    id="name" 
                    value="{{ old('name', $family->name) }}" 
                    placeholder="Enter family name"
                    required
                    autofocus
                    class="mt-1"
                />
                @error('name')
                    <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-4">
                <x-button type="submit" variant="primary" size="md">
                    Update Family
                </x-button>
                <a href="{{ route('families.show', $family) }}">
                    <x-button type="button" variant="outline" size="md">
                        Cancel
                    </x-button>
                </a>
            </div>
        </form>
        </div>
    </div>
</x-app-layout>





