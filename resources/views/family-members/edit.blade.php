<x-app-layout title="Edit Family Member: {{ $member->first_name }} {{ $member->last_name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Members', 'url' => route('families.show', $family) . '#members'],
            ['label' => $member->first_name . ' ' . $member->last_name, 'url' => route('families.members.show', [$family, $member])],
            ['label' => 'Edit']
        ]" />

        <div class="card card-contrast">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Edit Family Member</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Update information for {{ $member->first_name }} {{ $member->last_name }}
                </p>
            </div>

        <form method="POST" action="{{ route('families.members.update', [$family, $member]) }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-label for="avatar">Profile Photo</x-label>
                    <input
                        type="file"
                        name="avatar"
                        id="avatar"
                        accept="image/*"
                        class="mt-1 block w-full text-sm text-[var(--color-text-primary)] file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-[var(--color-surface)] file:text-[var(--color-text-primary)] hover:file:bg-[var(--color-surface-alt)]"
                    />
                    @error('avatar')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                    @if($member->avatar_url)
                        <div class="mt-3 flex items-center gap-3">
                            <img src="{{ $member->avatar_url }}" alt="Avatar preview" class="w-14 h-14 rounded-full object-cover border border-[var(--color-border-primary)]">
                            <p class="text-sm text-[var(--color-text-secondary)]">Current photo</p>
                        </div>
                    @endif
                </div>

                <div>
                    <x-label for="first_name" required>First Name</x-label>
                    <x-input
                        type="text"
                        name="first_name"
                        id="first_name"
                        value="{{ old('first_name', $member->first_name) }}"
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
                        value="{{ old('last_name', $member->last_name) }}"
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
                        <option value="male" {{ old('gender', $member->gender) == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $member->gender) == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $member->gender) == 'other' ? 'selected' : '' }}>Other</option>
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
                        value="{{ old('date_of_birth', $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '') }}"
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
                        value="{{ old('relation', $member->relation) }}"
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
                        value="{{ old('phone', $member->phone) }}"
                        placeholder="Enter phone number"
                        class="mt-1"
                    />
                    @error('phone')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="email">Email</x-label>
                    <x-input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', $member->email) }}"
                        placeholder="Enter email address"
                        class="mt-1"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="is_deceased">Status</x-label>
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                name="is_deceased"
                                id="is_deceased"
                                value="1"
                                {{ old('is_deceased', $member->is_deceased) ? 'checked' : '' }}
                                onchange="document.getElementById('date_of_death_field').classList.toggle('hidden', !this.checked)"
                                class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                            />
                            <span class="ml-2 text-sm text-[var(--color-text-primary)]">Mark as Deceased</span>
                        </label>
                    </div>
                    @error('is_deceased')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div id="date_of_death_field" class="{{ old('is_deceased', $member->is_deceased) ? '' : 'hidden' }}">
                    <x-label for="date_of_death">Date of Death</x-label>
                    <x-input
                        type="date"
                        name="date_of_death"
                        id="date_of_death"
                        value="{{ old('date_of_death', $member->date_of_death ? $member->date_of_death->format('Y-m-d') : '') }}"
                        class="mt-1"
                    />
                    @error('date_of_death')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <x-button type="submit" variant="primary" size="md">
                    Update Member
                </x-button>
                <a href="{{ route('families.members.show', [$family, $member]) }}">
                    <x-button type="button" variant="outline" size="md">
                        Cancel
                    </x-button>
                </a>
            </div>
        </form>
        </div>
    </div>
</x-app-layout>



