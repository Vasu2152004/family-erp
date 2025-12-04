<x-app-layout title="Families">
    <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Families</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Manage your family groups and members
                </p>
            </div>
            <a href="{{ route('families.create') }}">
                <x-button variant="primary" size="md">
                    Create Family
                </x-button>
            </a>
        </div>

        @if($families->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($families as $family)
                    <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-xl font-semibold text-[var(--color-text-primary)]">
                                {{ $family->name }}
                            </h3>
                            <span class="text-xs text-[var(--color-text-secondary)]">
                                {{ $family->members_count }} {{ Str::plural('member', $family->members_count) }}
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-[var(--color-text-secondary)]">
                                <span class="font-medium">Members:</span>
                                <span class="ml-2">{{ $family->members_count }}</span>
                            </div>
                            <div class="flex items-center text-sm text-[var(--color-text-secondary)]">
                                <span class="font-medium">Roles:</span>
                                <span class="ml-2">{{ $family->roles_count }}</span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <a href="{{ route('families.show', $family) }}" class="flex-1">
                                <x-button variant="outline" size="sm" class="w-full">
                                    View
                                </x-button>
                            </a>
                            @can('update', $family)
                                <a href="{{ route('families.edit', $family) }}">
                                    <x-button variant="ghost" size="sm">
                                        Edit
                                    </x-button>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $families->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-[var(--color-text-secondary)] mb-4">No families found.</p>
                <a href="{{ route('families.create') }}">
                    <x-button variant="primary" size="md">
                        Create Your First Family
                    </x-button>
                </a>
            </div>
        @endif
    </div>
</x-app-layout>

