<x-auth-layout title="Forgot Password" subtitle="Enter your email to receive a password reset link">
    <x-form method="POST" action="{{ route('forgot-password') }}">
        @if(session('status'))
            <x-alert type="success" dismissible>
                {{ session('status') }}
            </x-alert>
        @endif

        <p class="text-sm text-[var(--color-text-secondary)]">
            Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
        </p>

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

        <div class="flex items-center justify-between">
            <x-link href="{{ route('login') }}" variant="secondary" class="text-sm">
                Back to login!
            </x-link>
            <x-button type="submit" variant="primary" size="md">
                Send Reset Link
            </x-button>
        </div>
    </x-form>
</x-auth-layout>

