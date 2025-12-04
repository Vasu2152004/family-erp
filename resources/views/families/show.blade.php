<x-app-layout title="{{ $family->name }}">
    <div class="space-y-6">
        <!-- Family Header -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $family->name }}</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Family Management Dashboard
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('update', $family)
                        <a href="{{ route('families.edit', $family) }}">
                            <x-button variant="outline" size="md">Edit Family</x-button>
                        </a>
                    @endcan
                    @can('manageFamily', $family)
                        <a href="{{ route('families.members.create', $family) }}">
                            <x-button variant="primary" size="md">Add Member</x-button>
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                    <p class="text-sm text-[var(--color-text-secondary)]">Total Members</p>
                    <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $family->members->count() }}</p>
                </div>
                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                    <p class="text-sm text-[var(--color-text-secondary)]">Active Roles</p>
                    <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $family->roles->count() }}</p>
                </div>
                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                    <p class="text-sm text-[var(--color-text-secondary)]">Alive Members</p>
                    <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $family->members->where('is_deceased', false)->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Members Section -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Family Members</h2>
                @can('manageFamily', $family)
                    <a href="{{ route('families.members.create', $family) }}">
                        <x-button variant="primary" size="sm">Add Member</x-button>
                    </a>
                @endcan
            </div>

            @if($family->members->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Relation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($family->members as $member)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-[var(--color-text-primary)]">
                                            {{ $member->first_name }} {{ $member->last_name }}
                                        </div>
                                        @if($member->user)
                                            <div class="text-xs text-[var(--color-text-secondary)]">
                                                Linked: {{ $member->user->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $member->relation }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($member->is_deceased)
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Deceased</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Alive</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('families.members.show', [$family, $member]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)] mb-4">No members added yet.</p>
                    @can('manageFamily', $family)
                        <a href="{{ route('families.members.create', $family) }}">
                            <x-button variant="primary" size="md">Add First Member</x-button>
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

