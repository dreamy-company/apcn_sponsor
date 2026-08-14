<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <x-input name="email" :label="__('Email address')" type="email" icon="o-envelope" error-field="email" required autofocus placeholder="email@example.com" />

            <x-button :label="__('Email password reset link')" type="submit" class="btn-primary w-full" data-test="email-password-reset-link-button" />
        </form>

        <div class="space-x-1 text-center text-sm text-base-content/60 rtl:space-x-reverse">
            <span>{{ __('Or, return to') }}</span>
            <a class="link link-primary" href="{{ route('login') }}" wire:navigate>{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
