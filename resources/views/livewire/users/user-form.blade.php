<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ $user ? __('Edit Administrator') : __('New Administrator') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('An administrator is a J4U committee member with sign-in access.') }}</p>
            </div>
            <x-button :label="__('Back')" icon="o-arrow-left" :link="route('users.index')" class="btn-ghost" />
        </div>

        @if (session('status'))
            <x-alert :title="session('status')" icon="o-check-circle" class="alert-success" />
        @endif

        <x-card>
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input :label="__('Name')" wire:model="name" :placeholder="__('Committee member name')" />
                    <x-input :label="__('Email')" wire:model="email" type="email" placeholder="admin@example.com" />

                    <x-password
                        :label="__('Password')"
                        :hint="$user ? __('leave blank to keep current') : __('min 8 characters')"
                        wire:model="password"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        right
                    />

                    <x-password
                        :label="__('Confirm Password')"
                        wire:model="passwordConfirmation"
                        error-field="password"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        right
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <x-button :label="__('Cancel')" :link="route('users.index')" class="btn-ghost" />
                    <x-button :label="$user ? __('Save Changes') : __('Create Administrator')" type="submit" class="btn-primary" spinner="save" />
                </div>
            </form>
        </x-card>
    </div>
</section>
