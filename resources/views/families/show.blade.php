<x-app-layout title="{{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name]
        ]" />

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
                    @php
                        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
                            ->where('user_id', Auth::id())
                            ->first();
                        $isOwner = $userRole && $userRole->role === 'OWNER';
                    @endphp
                    @unless($isOwner)
                        <x-form 
                            method="POST" 
                            action="{{ route('families.leave', $family) }}" 
                            class="inline"
                            data-confirm="Are you sure you want to leave this family? This action cannot be undone."
                            data-confirm-title="Leave Family"
                            data-confirm-variant="primary"
                        >
                            @csrf
                            <x-button type="submit" variant="outline" size="md">
                                Leave Family
                            </x-button>
                        </x-form>
                    @endunless
                </div>
            </div>

            @if($errors->has('family'))
                <div class="mb-6">
                    <x-alert type="error" dismissible class="animate-fade-in">
                        {{ $errors->first('family') }}
                    </x-alert>
                </div>
            @endif

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                    <p class="text-sm text-[var(--color-text-secondary)]">Total Members</p>
                    <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $family->members->count() + $owners->count() }}</p>
                </div>
                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                    <p class="text-sm text-[var(--color-text-secondary)]">Active Roles</p>
                    <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $family->roles->count() }}</p>
                </div>
                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                    <p class="text-sm text-[var(--color-text-secondary)]">Alive Members</p>
                    <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $family->members->where('is_deceased', false)->count() + $owners->count() }}</p>
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

            @php
                // Merge owners and members, sorted by creation date
                $allMembers = $owners->merge($family->members)->sortByDesc('created_at');
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
                                            <a href="{{ route('families.members.show', [$family, $member]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                                                View
                                            </a>
                                        @endif
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

        <!-- Requests Section -->
        @if($pendingMemberRequests->count() > 0 || $pendingAdminRequests || ($isOwnerOrAdmin && $adminRequestsToReview->count() > 0))
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Requests</h2>
                
                <!-- Family Member Requests (for current user) -->
                @if($pendingMemberRequests->count() > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Family Member Requests</h3>
                        <div class="space-y-4">
                            @foreach($pendingMemberRequests as $request)
                                <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <h4 class="font-semibold text-[var(--color-text-primary)]">
                                                {{ $request->first_name }} {{ $request->last_name }}
                                            </h4>
                                            <p class="text-sm text-[var(--color-text-secondary)]">
                                                Requested by {{ $request->requestedBy->name }} • Relation: {{ $request->relation }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <x-form method="POST" action="{{ route('family-member-requests.accept', $request) }}">
                                                @csrf
                                                <x-button type="submit" variant="primary" size="sm">Accept</x-button>
                                            </x-form>
                                            <x-form method="POST" action="{{ route('family-member-requests.reject', $request) }}">
                                                @csrf
                                                <x-button type="submit" variant="outline" size="sm">Reject</x-button>
                                            </x-form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Admin Role Requests (for current user) -->
                @if($pendingAdminRequests)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Your Admin Role Request</h3>
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4">
                            <p class="text-sm text-[var(--color-text-secondary)] mb-2">
                                Status: <span class="font-medium text-[var(--color-text-primary)]">Pending</span>
                            </p>
                            <p class="text-sm text-[var(--color-text-secondary)] mb-2">
                                Request Count: <span class="font-medium text-[var(--color-text-primary)]">{{ $pendingAdminRequests->request_count }} of 3</span>
                            </p>
                            @if(!$pendingAdminRequests->canRequestAgain())
                                <p class="text-sm text-yellow-600">
                                    ⏰ You can request again in {{ $pendingAdminRequests->getDaysUntilNextRequest() }} day(s)
                                </p>
                            @endif
                            @if($pendingAdminRequests->isEligibleForAutoPromotion())
                                <p class="text-sm text-blue-600 mt-2">
                                    <strong>Note:</strong> You have submitted 3 requests. You will be automatically promoted to ADMIN if admins don't respond.
                                </p>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Admin Role Requests to Review (for admins/owners) -->
                @if($isOwnerOrAdmin && $adminRequestsToReview->count() > 0)
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Admin Role Requests to Review</h3>
                        <div class="space-y-4">
                            @foreach($adminRequestsToReview as $adminRequest)
                                <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <h4 class="font-semibold text-[var(--color-text-primary)]">
                                                {{ $adminRequest->user->name }} ({{ $adminRequest->user->email }})
                                            </h4>
                                            <p class="text-sm text-[var(--color-text-secondary)]">
                                                Request #{{ $adminRequest->request_count }} of 3
                                            </p>
                                            <p class="text-xs text-[var(--color-text-tertiary)] mt-1">
                                                Requested {{ $adminRequest->last_requested_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <x-form method="POST" action="{{ route('families.roles.approve-admin-request', $family) }}">
                                                @csrf
                                                <input type="hidden" name="request_id" value="{{ $adminRequest->id }}">
                                                <x-button type="submit" variant="primary" size="sm">Approve</x-button>
                                            </x-form>
                                            <x-form method="POST" action="{{ route('families.roles.reject-admin-request', $family) }}">
                                                @csrf
                                                <input type="hidden" name="request_id" value="{{ $adminRequest->id }}">
                                                <x-button type="submit" variant="outline" size="sm">Reject</x-button>
                                            </x-form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Finance Section - Quick Link -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Finance & Expenses</h2>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage all finance activities for this family
                    </p>
                </div>
                <a href="{{ route('finance.index', ['family_id' => $family->id]) }}">
                    <x-button variant="primary" size="md">Go to Finance Module</x-button>
                </a>
            </div>
        </div>

        <!-- Roles Section -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Family Roles</h2>
                <a href="{{ route('families.roles.index', $family) }}">
                    <x-button variant="primary" size="sm">Manage Roles</x-button>
                </a>
            </div>

            @if($family->roles->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">User</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Backup Admin</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($family->roles as $role)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-[var(--color-text-primary)]">
                                        {{ $role->user->name }} ({{ $role->user->email }})
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($role->role === 'OWNER') bg-purple-100 text-purple-800
                                            @elseif($role->role === 'ADMIN') bg-blue-100 text-blue-800
                                            @elseif($role->role === 'MEMBER') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $role->role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">
                                        @if($role->is_backup_admin)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Yes</span>
                                        @else
                                            <span class="text-[var(--color-text-tertiary)]">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-[var(--color-text-secondary)]">No roles assigned for this family yet.</p>
            @endif
        </div>
    </div>
</x-app-layout>

