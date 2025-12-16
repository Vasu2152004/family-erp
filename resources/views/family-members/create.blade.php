<x-app-layout title="Add Family Member">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Members', 'url' => route('families.show', $family) . '#members'],
            ['label' => 'Add Member']
        ]" />

        <div class="card card-contrast">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Add Family Member</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Send a request to add a new member to {{ $family->name }}
                </p>
            </div>

        <form method="POST" action="{{ route('families.members.store', $family) }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-label for="first_name" required>First Name</x-label>
                    <x-input
                        type="text"
                        name="first_name"
                        id="first_name"
                        value="{{ old('first_name') }}"
                        placeholder="Enter first name"
                        required
                        autofocus
                        class="mt-1"
                    />
                    @error('first_name')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="last_name" required>Last Name</x-label>
                    <x-input
                        type="text"
                        name="last_name"
                        id="last_name"
                        value="{{ old('last_name') }}"
                        placeholder="Enter last name"
                        required
                        class="mt-1"
                    />
                    @error('last_name')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="gender" required>Gender</x-label>
                    <select name="gender" id="gender" required class="mt-1 block w-full rounded-xl border border-[var(--color-border-primary)] px-4 py-3 text-[var(--color-text-primary)] bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('gender')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="date_of_birth">Date of Birth</x-label>
                    <x-input
                        type="date"
                        name="date_of_birth"
                        id="date_of_birth"
                        value="{{ old('date_of_birth') }}"
                        class="mt-1"
                    />
                    @error('date_of_birth')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="relation" required>Relation</x-label>
                    <x-input
                        type="text"
                        name="relation"
                        id="relation"
                        value="{{ old('relation') }}"
                        placeholder="e.g., Father, Mother, Son, Daughter"
                        required
                        class="mt-1"
                    />
                    @error('relation')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="phone">Phone</x-label>
                    <x-input
                        type="tel"
                        name="phone"
                        id="phone"
                        value="{{ old('phone') }}"
                        placeholder="Enter phone number"
                        class="mt-1"
                    />
                    @error('phone')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <x-label for="email">User Email (Required)</x-label>
                    <x-input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        placeholder="Enter email address of existing user"
                        required
                        class="mt-1"
                    />
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">
                        The user must exist in the system with this email to send a request. If the user doesn't exist, they need to register first.
                    </p>
                    @error('email')
                        <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-800 font-medium">{{ $message }}</p>
                            <p class="mt-1 text-xs text-red-600">
                                💡 Tip: The user needs to create an account first before you can send them a family member request.
                            </p>
                        </div>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <x-label for="user_id">Or User ID (Optional, for fast lookup)</x-label>
                    <x-input
                        type="number"
                        name="user_id"
                        id="user_id"
                        value="{{ old('user_id') }}"
                        placeholder="Enter user ID for fast lookup"
                        class="mt-1"
                    />
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">
                        If provided, this will be used to identify the user.
                    </p>
                    @error('user_id')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <x-button type="submit" variant="primary" size="md">
                    Send Request
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

