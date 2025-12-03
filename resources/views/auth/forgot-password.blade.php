<x-auth-layout title="Forgot Password" subtitle="Enter your email to receive a password reset link">
    <form method="POST" action="{{ route('forgot-password') }}" class="space-y-6">
        @csrf

        @if(session('status'))
            <x-alert type="success" dismissible>
                {{ session('status') }}
            </x-alert>
        @endif

        <p class="text-sm text-[var(--color-text-secondary)]">
            Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
        </p>

        <!-- Email Address -->
        <div>
            <x-label for="email" :required="true">Email Address</x-label>
            <x-input
                type="email"
                name="email"
                id="email"
                :value="old('email')"
                placeholder="Enter your email"
                :required="true"
                :autofocus="true"
                :error="$errors->has('email')"
            />
            @error('email')
                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-between">
            <a href="{{ route('login') }}" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">
                Back to login
            </a>
            <x-button type="submit" variant="primary" size="md">
                Send Reset Link
            </x-button>
        </div>
    </form>
</x-auth-layout>

