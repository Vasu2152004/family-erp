<x-app-layout title="Family Roles: {{ $family->name }}">
    <div class="space-y-6">
        <!-- Family Header -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Family Roles</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage roles and permissions for {{ $family->name }}
                    </p>
                </div>
                <a href="{{ route('families.show', $family) }}">
                    <x-button variant="outline" size="md">Back to Family</x-button>
                </a>
            </div>
        </div>

        <!-- Admin Role Request Section -->
        @php
            // Use the isOwnerOrAdmin from controller (already calculated with fresh data)
            // If not set, calculate it fresh
            if (!isset($isOwnerOrAdmin)) {
                $userRole = $family->roles()->where('user_id', Auth::id())->first();
                $isOwnerOrAdmin = $userRole && ($userRole->role === 'OWNER' || $userRole->role === 'ADMIN');
            }
            
            $hasActiveAdmins = \App\Models\FamilyUserRole::where('family_id', $family->id)
                ->whereIn('role', ['OWNER', 'ADMIN'])
                ->whereHas('user', function ($query) {
                    $query->whereHas('familyMember', function ($q) {
                        $q->where('is_deceased', false);
                    })->orWhereDoesntHave('familyMember');
                })
                ->exists();
        @endphp

        @if(isset($userAdminRequest) && $userAdminRequest && !$isOwnerOrAdmin)
            @if($userAdminRequest->status === 'auto_promoted')
                <div class="bg-green-50 border border-green-200 rounded-xl shadow-lg p-6 mb-6">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h2 class="text-xl font-bold text-green-800">Congratulations! You've been promoted to ADMIN</h2>
                            <p class="text-sm text-green-700 mt-1">You have been automatically promoted to ADMIN role after 3 requests with no response from admins.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                    <h2 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">Your Admin Role Request</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-[var(--color-text-secondary)]">
                                    Request Status: <span class="font-medium text-[var(--color-text-primary)]">Pending</span>
                                </p>
                                <p class="text-sm text-[var(--color-text-secondary)] mt-1">
                                    Request Count: <span class="font-medium text-[var(--color-text-primary)]">{{ $userAdminRequest->request_count }} of 3</span>
                                </p>
                            </div>
                            @if(!$userAdminRequest->canRequestAgain())
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <p class="text-sm text-yellow-800 font-medium">
                                        ⏰ Cooldown Active
                                    </p>
                                    <p class="text-xs text-yellow-600 mt-1">
                                        You can request again in {{ $userAdminRequest->getDaysUntilNextRequest() }} day(s)
                                    </p>
                                </div>
                            @else
                                <form action="{{ route('families.roles.request-admin', $family) }}" method="POST">
                                    @csrf
                                    <x-button type="submit" variant="primary" size="sm">
                                        Submit Request #{{ $userAdminRequest->request_count + 1 }}
                                    </x-button>
                                </form>
                            @endif
                        </div>
                        @if($userAdminRequest->isEligibleForAutoPromotion())
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <p class="text-sm text-blue-800">
                                    <strong>Note:</strong> You have submitted 3 requests. You will be automatically promoted to ADMIN role immediately if admins don't respond, regardless of whether active admins exist.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        @if(!$isOwnerOrAdmin && (!isset($userAdminRequest) || !$userAdminRequest))
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                <h2 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">Request Admin Role</h2>
                @if(!$hasActiveAdmins)
                    <p class="text-sm text-[var(--color-text-secondary)] mb-4">
                        No active admins exist for this family. You can request admin role. You need to submit 3 requests (with 2 days between each) to be automatically promoted to ADMIN.
                    </p>
                @else
                    <p class="text-sm text-[var(--color-text-secondary)] mb-4">
                        You can request admin role. You need to submit 3 requests (with 2 days between each) to be automatically promoted to ADMIN if admins don't respond, regardless of whether active admins exist.
                    </p>
                @endif
                <form action="{{ route('families.roles.request-admin', $family) }}" method="POST">
                    @csrf
                    <x-button type="submit" variant="primary" size="md">
                        Request Admin Role
                    </x-button>
                </form>
            </div>
        @endif

        <!-- Current Roles Section -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Current Roles</h2>
                @can('manageFamily', $family)
                    <button onclick="document.getElementById('assignRoleForm').classList.toggle('hidden')" class="px-4 py-2 bg-[var(--color-primary)] text-white rounded-lg hover:bg-[var(--color-primary-dark)]">
                        Assign Role
                    </button>
                @endcan
            </div>

            @if($roles->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">User</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Backup Admin</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($roles as $role)
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
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @can('manageFamily', $family)
                                            <div class="flex items-center gap-2 justify-end">
                                                @if($role->role === 'OWNER' && Auth::user()->isFamilyOwner($family->id))
                                                    @if($role->is_backup_admin)
                                                        <form action="{{ route('families.roles.remove-backup-admin', $family) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="user_id" value="{{ $role->user_id }}">
                                                            <x-button type="submit" variant="outline" size="sm">Remove Backup</x-button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('families.roles.backup-admin', $family) }}" method="POST" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="user_id" value="{{ $role->user_id }}">
                                                            <x-button type="submit" variant="outline" size="sm">Set Backup</x-button>
                                                        </form>
                                                    @endif
                                                @endif
                                                @if($isOwnerOrAdmin && $role->role !== 'OWNER')
                                                    <button onclick="document.getElementById('editRoleForm{{ $role->id }}').classList.toggle('hidden')" class="px-3 py-1 text-xs border border-[var(--color-border-primary)] rounded-lg text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)]">
                                                        Edit
                                                    </button>
                                                @endif
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                                @can('manageFamily', $family)
                                    @if($isOwnerOrAdmin && $role->role !== 'OWNER')
                                        <!-- Edit Role Form (Hidden by default) -->
                                        <tr id="editRoleForm{{ $role->id }}" class="hidden">
                                            <td colspan="4" class="px-6 py-4 bg-[var(--color-bg-secondary)]">
                                                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                                                    <h4 class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Edit Role for {{ $role->user->name }}</h4>
                                                    <form action="{{ route('families.roles.assign', $family) }}" method="POST" class="space-y-3">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{ $role->user_id }}">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                            <div>
                                                                <x-label for="edit_role_{{ $role->id }}" required>Role</x-label>
                                                                <select name="role" id="edit_role_{{ $role->id }}" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                                                    <option value="ADMIN" {{ $role->role === 'ADMIN' ? 'selected' : '' }}>ADMIN</option>
                                                                    <option value="MEMBER" {{ $role->role === 'MEMBER' ? 'selected' : '' }}>MEMBER</option>
                                                                    <option value="VIEWER" {{ $role->role === 'VIEWER' ? 'selected' : '' }}>VIEWER</option>
                                                                </select>
                                                            </div>
                                                            <div class="flex items-center pt-7">
                                                                <input type="checkbox" name="is_backup_admin" id="edit_backup_{{ $role->id }}" value="1" {{ $role->is_backup_admin ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                                                                <x-label for="edit_backup_{{ $role->id }}" class="ml-2">Set as Backup Admin</x-label>
                                                            </div>
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <x-button type="submit" variant="primary" size="sm">Update Role</x-button>
                                                            <button type="button" onclick="document.getElementById('editRoleForm{{ $role->id }}').classList.add('hidden')" class="px-3 py-1.5 text-sm border border-[var(--color-border-primary)] rounded-lg text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)]">
                                                                Cancel
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endcan
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-[var(--color-text-secondary)]">No roles assigned for this family yet.</p>
            @endif

            <!-- Assign Role Form (Hidden by default) -->
            @can('manageFamily', $family)
                <div id="assignRoleForm" class="hidden mt-6 bg-[var(--color-bg-secondary)] rounded-lg p-6 border border-[var(--color-border-primary)]">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Assign New Role</h3>
                    <p class="text-sm text-[var(--color-text-secondary)] mb-4">
                        Assign a role to a user. If the user already has a role, it will be updated.
                    </p>
                    <form action="{{ route('families.roles.assign', $family) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <x-label for="user_id" required>User</x-label>
                            <select name="user_id" id="user_id" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                <option value="">Select a user...</option>
                                @foreach($allUsers as $user)
                                    @php
                                        $existingRole = $roles->firstWhere('user_id', $user->id);
                                        $roleText = $existingRole ? " (Current: {$existingRole->role})" : '';
                                    @endphp
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }}){{ $roleText }}</option>
                                @endforeach
                            </select>
                            @if($allUsers->count() === 0)
                                <p class="mt-1 text-xs text-[var(--color-text-secondary)]">No other users available in your tenant.</p>
                            @endif
                            @error('user_id')
                                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <x-label for="role" required>Role</x-label>
                            <select name="role" id="role" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                <option value="">Select a role...</option>
                                @if(Auth::user()->isFamilyOwner($family->id))
                                    <option value="OWNER">OWNER</option>
                                @endif
                                <option value="ADMIN">ADMIN</option>
                                <option value="MEMBER">MEMBER</option>
                                <option value="VIEWER">VIEWER</option>
                            </select>
                            @if($isOwnerOrAdmin && !Auth::user()->isFamilyOwner($family->id))
                                <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Note: Only owners can assign OWNER role.</p>
                            @endif
                            @error('role')
                                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_backup_admin" id="is_backup_admin" value="1" class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                            <x-label for="is_backup_admin" class="ml-2">Set as Backup Admin</x-label>
                        </div>
                        <div class="flex gap-4">
                            <x-button type="submit" variant="primary" size="md">Assign Role</x-button>
                            <button type="button" onclick="document.getElementById('assignRoleForm').classList.add('hidden')" class="px-4 py-2 border border-[var(--color-border-primary)] rounded-lg text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)]">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>

