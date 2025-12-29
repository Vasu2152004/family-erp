<x-app-layout title="Edit Profile">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Profile'],
        ]" />

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Edit Profile</h2>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Profile Photo -->
                    <div>
                        <x-label for="avatar">Profile Photo</x-label>
                        <input
                            type="file"
                            name="avatar"
                            id="avatar"
                            accept="image/*"
                            class="mt-1 block w-full text-sm text-[var(--color-text-primary)] file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-[var(--color-surface)] file:text-[var(--color-text-primary)] hover:file:bg-[var(--color-surface-alt)]"
                            onchange="previewAvatar(this)"
                        />
                        @error('avatar')
                            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                        @enderror
                        @if($user->avatar_url)
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ $user->avatar_url }}" alt="Avatar preview" id="avatarPreview" class="w-20 h-20 rounded-full object-cover border border-[var(--color-border-primary)]">
                                <p class="text-sm text-[var(--color-text-secondary)]">Current photo</p>
                            </div>
                        @else
                            <div class="mt-3 flex items-center gap-3">
                                <div id="avatarPreview" class="w-20 h-20 rounded-full bg-[var(--color-primary)]/10 border border-[var(--color-border-primary)] flex items-center justify-center text-[var(--color-primary)] font-semibold text-xl">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <p class="text-sm text-[var(--color-text-secondary)]">No photo uploaded</p>
                            </div>
                        @endif
                    </div>

                    <!-- User ID -->
                    <div>
                        <x-label>User ID</x-label>
                        <div class="mt-1 flex items-center gap-2">
                            <input
                                type="text"
                                value="{{ $user->id }}"
                                readonly
                                class="block w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
                            />
                            <button
                                type="button"
                                onclick="copyUserId()"
                                class="px-3 py-2 rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)] transition-colors"
                                title="Copy User ID"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Use this ID when adding family members</p>
                    </div>

                    <!-- Name -->
                    <div>
                        <x-label for="name" required>Name</x-label>
                        <x-input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="mt-1" />
                        @error('name')
                            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <x-label for="email" required>Email</x-label>
                        <x-input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-1" />
                        @error('email')
                            <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <x-button type="submit" variant="primary" size="md">Update Profile</x-button>
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function previewAvatar(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('avatarPreview');
                        if (preview.tagName === 'IMG') {
                            preview.src = e.target.result;
                        } else {
                            // Replace div with img
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'w-20 h-20 rounded-full object-cover border border-[var(--color-border-primary)]';
                            img.id = 'avatarPreview';
                            preview.parentNode.replaceChild(img, preview);
                        }
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function copyUserId() {
                const userId = {{ $user->id }};
                navigator.clipboard.writeText(userId.toString()).then(function() {
                    // Show feedback
                    const button = event.target.closest('button');
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                    }, 2000);
                });
            }
        </script>
    @endpush
</x-app-layout>
