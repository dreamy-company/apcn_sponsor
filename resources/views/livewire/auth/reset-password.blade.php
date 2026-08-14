<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <x-input name="email" value="{{ request('email') }}" :label="__('Email')" type="email" icon="o-envelope" error-field="email" required autocomplete="email" />
            <x-password name="password" :label="__('Password')" icon="o-lock-closed" error-field="password" required autocomplete="new-password" :placeholder="__('Password')" right />
            <x-password name="password_confirmation" :label="__('Confirm password')" icon="o-lock-closed" required autocomplete="new-password" :placeholder="__('Confirm password')" right />

            <x-button :label="__('Reset password')" type="submit" class="btn-primary w-full" data-test="reset-password-button" />
        </form>
    </div>
</x-layouts::auth>
