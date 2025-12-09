<x-app-layout title="Family Member: {{ $member->first_name }} {{ $member->last_name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Members', 'url' => route('families.show', $family) . '#members'],
            ['label' => $member->first_name . ' ' . $member->last_name]
        ]" />

        <!-- Header -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $member->first_name }} {{ $member->last_name }}</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Family Member Details
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('manageFamily', $family)
                        <a href="{{ route('families.members.edit', [$family, $member]) }}">
                            <x-button variant="primary" size="md">Edit Member</x-button>
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Member Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Personal Information -->
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                <h2 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">Personal Information</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-[var(--color-text-secondary)]">Full Name</dt>
                        <dd class="mt-1 text-sm text-[var(--color-text-primary)]">{{ $member->first_name }} {{ $member->last_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-[var(--color-text-secondary)]">Gender</dt>
                        <dd class="mt-1 text-sm text-[var(--color-text-primary)] capitalize">{{ $member->gender }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-[var(--color-text-secondary)]">Date of Birth</dt>
                        <dd class="mt-1 text-sm text-[var(--color-text-primary)]">
                            {{ $member->date_of_birth ? $member->date_of_birth->format('F d, Y') : 'Not specified' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-[var(--color-text-secondary)]">Relation</dt>
                        <dd class="mt-1 text-sm text-[var(--color-text-primary)]">{{ $member->relation }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Contact Information -->
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                <h2 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">Contact Information</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-[var(--color-text-secondary)]">Email</dt>
                        <dd class="mt-1 text-sm text-[var(--color-text-primary)]">
                            {{ $member->email ?: 'Not provided' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-[var(--color-text-secondary)]">Phone</dt>
                        <dd class="mt-1 text-sm text-[var(--color-text-primary)]">
                            {{ $member->phone ?: 'Not provided' }}
                        </dd>
                    </div>
                    @if($member->user)
                        <div>
                            <dt class="text-sm font-medium text-[var(--color-text-secondary)]">Linked User Account</dt>
                            <dd class="mt-1 text-sm text-[var(--color-text-primary)]">
                                {{ $member->user->name }} ({{ $member->user->email }})
                            </dd>
                        </div>
                    @else
                        <div>
                            <dt class="text-sm font-medium text-[var(--color-text-secondary)]">User Account</dt>
                            <dd class="mt-1 text-sm text-[var(--color-text-tertiary)]">Not linked to any user account</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Status Information -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <h2 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">Status</h2>
            <div class="flex items-center space-x-4">
                @if($member->is_deceased)
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">Deceased</span>
                        @if($member->date_of_death)
                            <span class="text-sm text-[var(--color-text-secondary)]">
                                Date of Death: {{ $member->date_of_death->format('F d, Y') }}
                            </span>
                        @endif
                    </div>
                @else
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Alive</span>
                @endif
            </div>
        </div>

        <!-- Actions -->
        @can('manageFamily', $family)
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
                <h2 class="text-xl font-bold text-[var(--color-text-primary)] mb-4">Actions</h2>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('families.members.edit', [$family, $member]) }}">
                        <x-button variant="primary" size="md">Edit Member</x-button>
                    </a>
                    @if(!$member->user)
                        <button onclick="document.getElementById('linkUserForm').classList.toggle('hidden')" class="px-4 py-2 bg-[var(--color-primary)] text-white rounded-lg hover:bg-[var(--color-primary-dark)]">
                            Link to User Account
                        </button>
                    @endif
                    <form action="{{ route('families.members.destroy', [$family, $member]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this family member?');">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="outline" size="md" class="text-red-600 border-red-300 hover:bg-red-50">
                            Delete Member
                        </x-button>
                    </form>
                </div>

                @if(!$member->user)
                    <!-- Link User Form (Hidden by default) -->
                    <div id="linkUserForm" class="hidden mt-6 bg-[var(--color-bg-secondary)] rounded-lg p-6 border border-[var(--color-border-primary)]">
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Link to User Account</h3>
                        <form action="{{ route('families.members.link-user', [$family, $member]) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <x-label for="user_id" required>Select User</x-label>
                                <select name="user_id" id="user_id" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                    <option value="">Select a user...</option>
                                    @php
                                        $allUsers = \App\Models\User::orderBy('name')->get();
                                    @endphp
                                    @foreach($allUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex gap-2">
                                <x-button type="submit" variant="primary" size="md">Link User</x-button>
                                <button type="button" onclick="document.getElementById('linkUserForm').classList.add('hidden')" class="px-4 py-2 border border-[var(--color-border-primary)] rounded-lg text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)]">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        @endcan
    </div>
</x-app-layout>



