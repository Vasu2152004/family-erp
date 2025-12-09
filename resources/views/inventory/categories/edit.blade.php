<x-app-layout title="Edit Inventory Category: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Inventory', 'url' => route('inventory.items.index', ['family_id' => $family->id])],
            ['label' => 'Categories', 'url' => route('inventory.categories.index', ['family_id' => $family->id])],
            ['label' => 'Edit']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Edit Inventory Category</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Update category details
                </p>
            </div>

            <form method="POST" action="{{ route('inventory.categories.update', ['category' => $category->id, 'family_id' => $family->id]) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                <div>
                    <x-label for="name" required>Category Name</x-label>
                    <x-input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required class="mt-1" />
                    @error('name')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="description">Description</x-label>
                    <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="icon">Icon (Emoji)</x-label>
                    <x-input type="text" name="icon" id="icon" value="{{ old('icon', $category->icon) }}" placeholder="e.g., 🍎" class="mt-1" />
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Optional: Add an emoji icon for this category</p>
                    @error('icon')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="color">Color</x-label>
                    <x-input type="color" name="color" id="color" value="{{ old('color', $category->color ?? '#3b82f6') }}" class="mt-1 h-12" />
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Optional: Choose a color for this category</p>
                    @error('color')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4">
                    <x-button type="submit" variant="primary" size="md">Update Category</x-button>
                    <a href="{{ route('inventory.categories.index', ['family_id' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">Cancel</x-button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

