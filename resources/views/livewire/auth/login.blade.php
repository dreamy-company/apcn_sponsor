<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                icon="o-envelope"
                error-field="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div>
                <x-password
                    name="password"
                    :label="__('Password')"
                    type="password"
                    icon="o-lock-closed"
                    error-field="email"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    right
                />

                @if (Route::has('password.request'))
                    <div class="mt-1 text-end">
                        <a class="link link-primary text-sm" href="{{ route('password.request') }}" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Remember Me -->
            <x-checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <x-button :label="__('Log in')" type="submit" class="btn-primary w-full" data-test="login-button" />
        </form>

        <div class="space-x-1 text-center text-sm text-base-content/60 rtl:space-x-reverse">
            <span>{{ __('Don\'t have an account?') }}</span>
            <a class="link link-primary" href="{{ route('register') }}" wire:navigate>{{ __('Sign up') }}</a>
        </div>
    </div>
</x-layouts::auth>
