<x-auth-layout title="Create Account" subtitle="Sign up to get started">
    <x-form method="POST" action="{{ route('register') }}" id="register-form">
        <x-form-field label="Full Name" labelFor="name" :required="true" :error="$errors->has('name')">
            <x-input
                type="text"
                name="name"
                id="name"
                :value="old('name')"
                placeholder="Enter your full name"
                :required="true"
                :autofocus="true"
                :error="$errors->has('name')"
            />
            <x-error-message field="name" />
        </x-form-field>

        <x-form-field label="Email Address" labelFor="email" :required="true" :error="$errors->has('email')">
            <x-input
                type="email"
                name="email"
                id="email"
                :value="old('email')"
                placeholder="Enter your email"
                :required="true"
                :error="$errors->has('email')"
            />
            <x-error-message field="email" />
        </x-form-field>

        <x-form-field label="Password" labelFor="password" :required="true" :error="$errors->has('password')">
            <x-password-input
                name="password"
                id="password"
                placeholder="Create a password"
                :required="true"
                :error="$errors->has('password')"
            />
            <x-error-message field="password" />
        </x-form-field>

        <x-form-field label="Confirm Password" labelFor="password_confirmation" :required="true" :error="$errors->has('password_confirmation')">
            <x-password-input
                name="password_confirmation"
                id="password_confirmation"
                placeholder="Confirm your password"
                :required="true"
                :error="$errors->has('password_confirmation')"
                toggleId="toggle-password-confirmation"
            />
            <x-error-message field="password_confirmation" />
        </x-form-field>

        <div>
            <x-button type="submit" variant="primary" size="lg" class="w-full">
                Create Account
            </x-button>
        </div>
    </x-form>

    <x-slot name="footer">
        <p class="text-sm text-[var(--color-text-secondary)]">
            Already have an account?
            <x-link href="{{ route('login') }}" variant="primary">
                Sign in
            </x-link>
        </p>
    </x-slot>
</x-auth-layout>

