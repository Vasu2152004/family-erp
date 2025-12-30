<x-app-layout title="Family Members">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Families', 'url' => route('families.index')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Members']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Family Members</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        {{ $family->name }}
                    </p>
                </div>
                @can('manageFamily', $family)
                    <a href="{{ route('families.members.create', $family) }}">
                        <x-button variant="primary" size="md">Add Member</x-button>
                    </a>
                @endcan
            </div>

            @php
                // Merge owners and members, sorted by creation date
                $allMembers = $owners->merge($members->items())->sortByDesc('created_at');
            @endphp
            @if($allMembers->count() > 0)
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
                            @foreach($allMembers as $member)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-[var(--color-text-primary)]">
                                            {{ $member->first_name }} {{ $member->last_name }}
                                            @if(isset($member->is_owner) && $member->is_owner)
                                                <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Owner</span>
                                            @endif
                                        </div>
                                        @if(isset($member->user) && $member->user)
                                            <div class="text-xs text-[var(--color-text-secondary)]">
                                                Linked: {{ $member->user->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        {{ $member->relation }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(isset($member->is_deceased) && $member->is_deceased)
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Deceased</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Alive</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if(isset($member->is_owner) && $member->is_owner)
                                            <span class="text-[var(--color-text-secondary)]">—</span>
                                        @else
                                            <div class="flex gap-2">
                                                <a href="{{ route('families.members.show', [$family, $member]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                                                    View
                                                </a>
                                                @can('manageFamily', $family)
                                                    <x-form 
                                                        method="DELETE" 
                                                        action="{{ route('families.members.destroy', [$family, $member]) }}" 
                                                        class="inline"
                                                        data-confirm="Are you sure you want to delete this family member? This action cannot be undone."
                                                        data-confirm-title="Delete Family Member"
                                                        data-confirm-variant="danger"
                                                    >
                                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                                            Delete
                                                        </button>
                                                    </x-form>
                                                @endcan
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $members->links() }}
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

