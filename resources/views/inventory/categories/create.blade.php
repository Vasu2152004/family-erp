<x-app-layout title="Create Inventory Category: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Inventory', 'url' => route('inventory.items.index', ['family_id' => $family->id])],
            ['label' => 'Categories', 'url' => route('inventory.categories.index', ['family_id' => $family->id])],
            ['label' => 'Create']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Create Inventory Category</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Add a new category for organizing inventory items
                </p>
            </div>

            <x-form method="POST" action="{{ route('inventory.categories.store', ['family_id' => $family->id]) }}" class="space-y-6">
                @csrf
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                <div>
                    <x-label for="name" required>Category Name</x-label>
                    <x-input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1" />
                    <x-error-message field="name" />
                </div>

                <div>
                    <x-label for="description">Description</x-label>
                    <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('description') }}</textarea>
                    <x-error-message field="description" />
                </div>

                <div>
                    <x-label for="icon">Icon (Emoji)</x-label>
                    <x-input type="text" name="icon" id="icon" value="{{ old('icon') }}" placeholder="e.g., 🍎" class="mt-1" />
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Optional: Add an emoji icon for this category</p>
                    <x-error-message field="icon" />
                </div>

                <div>
                    <x-label for="color">Color</x-label>
                    <x-input type="color" name="color" id="color" value="{{ old('color', '#3b82f6') }}" class="mt-1 h-12" />
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Optional: Choose a color for this category</p>
                    <x-error-message field="color" />
                </div>

                <div class="flex gap-4">
                    <x-button type="submit" variant="primary" size="md">Create Category</x-button>
                    <a href="{{ route('inventory.categories.index', ['family_id' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">Cancel</x-button>
                    </a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>

