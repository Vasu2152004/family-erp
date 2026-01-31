<x-auth-layout title="Login" subtitle="Sign in to your account">
    <x-form method="POST" action="{{ route('login') }}" id="login-form">
        @if(session('status'))
            <x-alert type="success" dismissible class="mb-4 animate-fade-in">
                {{ session('status') }}
            </x-alert>
        @endif

        <x-form-field label="Email Address" labelFor="email" :required="true" :error="$errors->has('email')">
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
            <x-error-message field="email" />
        </x-form-field>

        <x-form-field label="Password" labelFor="password" :required="true" :error="$errors->has('password')">
            <x-password-input
            name="password"
            id="password"
            placeholder="Enter your password"
            :required="true"
            :error="$errors->has('password')"
            />
            <div class="flex items-center justify-between mb-1.5 mt-1">
               
                <x-link href="{{ route('forgot-password') }}" variant="primary" class="text-sm" data-in-form="true">
                    Forgot password?
                </x-link>
            </div>
            <x-error-message field="password" />
        </x-form-field>

        <div class="flex items-center">
            <x-checkbox name="remember" id="remember" />
            <x-label for="remember" class="ml-2">
                Remember me
            </x-label>
        </div>

        <div>
            <x-button type="submit" variant="primary" size="lg" class="w-full">
                Sign In
            </x-button>
        </div>
    </x-form>

    <x-slot name="footer">
        <p class="text-sm text-[var(--color-text-secondary)]">
            Don't have an account?
            <x-link href="{{ route('register') }}" variant="primary" class="ml-1" useJsNav="true">
                Create one now
            </x-link>
        </p>
    </x-slot>
</x-auth-layout>

