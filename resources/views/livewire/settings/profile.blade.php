<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Profile settings') }}</h2>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <x-input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <x-input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <p class="mt-4 text-sm text-base-content/70">
                        {{ __('Your email address is unverified.') }}
                        <a class="link link-primary cursor-pointer" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </a>
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <x-button :label="__('Save')" type="submit" class="btn-primary" spinner="updateProfileInformation" />
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
