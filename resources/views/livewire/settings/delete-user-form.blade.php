<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h2 class="text-lg font-extrabold">{{ __('Delete account') }}</h2>
        <p class="text-base-content/60">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-button
        :label="__('Delete account')"
        class="btn-error"
        onclick="document.getElementById('confirm-user-deletion').showModal()"
    />

    <x-modal id="confirm-user-deletion" :title="__('Are you sure you want to delete your account?')" class="backdrop-blur-sm">
        <p class="mb-4 text-base-content/60">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <x-password wire:model="password" :label="__('Password')" right />

            <div class="flex justify-end gap-2">
                <x-button :label="__('Cancel')" type="button" onclick="document.getElementById('confirm-user-deletion').close()" />
                <x-button :label="__('Delete account')" type="submit" class="btn-error" spinner="deleteUser" />
            </div>
        </form>
    </x-modal>
</section>
