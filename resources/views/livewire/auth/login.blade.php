<x-layouts::auth.split :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col">
            <p class="eyebrow text-primary">APSN</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">{{ __('Welcome back') }}</h1>
            <p class="mt-1 text-base-content/60">{{ __('Sign in to manage sponsorship deals.') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

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
                placeholder="you@hospital.org"
            />

            <!-- Password -->
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

            <x-button :label="__('Log in')" type="submit" icon-right="o-arrow-right" class="btn-primary w-full" data-test="login-button" />
        </form>
    </div>
</x-layouts::auth.split>
