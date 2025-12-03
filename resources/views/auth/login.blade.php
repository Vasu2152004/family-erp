<x-auth-layout title="Login" subtitle="Sign in to your account">
    <form method="POST" action="{{ route('login') }}" class="space-y-6" id="login-form">
        @csrf

        @if(session('status'))
            <x-alert type="success" dismissible>
                {{ session('status') }}
            </x-alert>
        @endif

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

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <x-label for="password" :required="true">Password</x-label>
                <a href="{{ route('forgot-password') }}" class="text-sm text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                    Forgot password?
                </a>
            </div>
            <div class="relative">
                <x-input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Enter your password"
                    :required="true"
                    :error="$errors->has('password')"
                    class="pr-10"
                />
                <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]"
                    id="toggle-password"
                    aria-label="Toggle password visibility"
                >
                    <svg class="w-5 h-5" id="eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg class="w-5 h-5 hidden" id="eye-off-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input
                type="checkbox"
                name="remember"
                id="remember"
                class="w-4 h-4 text-[var(--color-primary)] bg-[var(--color-bg-primary)] border-[var(--color-border-primary)] rounded focus:ring-[var(--color-primary)] focus:ring-2"
            >
            <label for="remember" class="ml-2 block text-sm text-[var(--color-text-secondary)]">
                Remember me
            </label>
        </div>

        <!-- Submit Button -->
        <div>
            <x-button type="submit" variant="primary" size="lg" class="w-full">
                Sign In
            </x-button>
        </div>
    </form>

    <x-slot name="footer">
        <p class="text-sm text-[var(--color-text-secondary)]">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">
                Sign up
            </a>
        </p>
    </x-slot>
</x-auth-layout>

