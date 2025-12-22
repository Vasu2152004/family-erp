<x-app-layout title="My Profile">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'My Profile']
        ]" />

        <div class="card">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">My Profile</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Update your account details
                </p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <x-label for="name" required>Name</x-label>
                    <x-input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="mt-1"
                    />
                    @error('name')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="email" required>Email</x-label>
                    <x-input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="mt-1"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-label for="password">New Password (optional)</x-label>
                        <x-input
                            type="password"
                            name="password"
                            id="password"
                            minlength="8"
                            maxlength="255"
                            class="mt-1"
                        />
                        @error('password')
                            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-label for="password_confirmation">Confirm Password</x-label>
                        <x-input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            minlength="8"
                            maxlength="255"
                            class="mt-1"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-[var(--color-border-primary)]">
                    <x-button type="submit" variant="primary" size="md">Save Changes</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>



