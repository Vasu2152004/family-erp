<x-app-layout title="Inventory Categories: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Inventory', 'url' => route('inventory.items.index', ['family_id' => $family->id])],
            ['label' => 'Categories']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Inventory Categories</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage inventory categories for {{ $family->name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('create', [\App\Models\InventoryCategory::class, $family])
                        <a href="{{ route('inventory.categories.create', ['family_id' => $family->id]) }}">
                            <x-button variant="primary" size="md">Create Category</x-button>
                        </a>
                    @endcan
                </div>
            </div>

            @if($categories->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($categories as $category)
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    @if($category->icon)
                                        <div class="text-2xl">{{ $category->icon }}</div>
                                    @endif
                                    <div>
                                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $category->name }}</h3>
                                        @if($category->description)
                                            <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $category->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if($category->color)
                                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $category->color }};"></div>
                                @endif
                            </div>
                            <div class="flex gap-2 mt-4">
                                @can('update', $category)
                                    <a href="{{ route('inventory.categories.edit', ['category' => $category->id, 'family_id' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)] text-sm">
                                        Edit
                                    </a>
                                @endcan
                                @can('delete', $category)
                                    <x-form method="POST" action="{{ route('inventory.categories.destroy', ['category' => $category->id, 'family_id' => $family->id]) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                    </x-form>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $categories->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)]">No categories found. Create your first category to get started.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

