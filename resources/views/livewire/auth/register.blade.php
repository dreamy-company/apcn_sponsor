<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <x-input name="name" :label="__('Name')" :value="old('name')" type="text" icon="o-user" error-field="name" required autofocus autocomplete="name" :placeholder="__('Full name')" />
            <x-input name="email" :label="__('Email address')" :value="old('email')" type="email" icon="o-envelope" error-field="email" required autocomplete="email" placeholder="email@example.com" />
            <x-password name="password" :label="__('Password')" icon="o-lock-closed" error-field="password" required autocomplete="new-password" :placeholder="__('Password')" right />
            <x-password name="password_confirmation" :label="__('Confirm password')" icon="o-lock-closed" required autocomplete="new-password" :placeholder="__('Confirm password')" right />

            <x-button :label="__('Create account')" type="submit" class="btn-primary w-full" data-test="register-user-button" />
        </form>

        <div class="space-x-1 text-center text-sm text-base-content/60 rtl:space-x-reverse">
            <span>{{ __('Already have an account?') }}</span>
            <a class="link link-primary" href="{{ route('login') }}" wire:navigate>{{ __('Log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
