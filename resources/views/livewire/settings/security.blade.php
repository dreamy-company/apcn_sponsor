<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Security settings') }}</h2>

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <x-password wire:model="current_password" :label="__('Current password')" required autocomplete="current-password" right />
            <x-password wire:model="password" :label="__('New password')" required autocomplete="new-password" right />
            <x-password wire:model="password_confirmation" :label="__('Confirm password')" error-field="password" required autocomplete="new-password" right />

            <div class="flex items-center gap-4">
                <x-button :label="__('Save')" type="submit" class="btn-primary" spinner="updatePassword" data-test="update-password-button" />
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <h3 class="text-lg font-extrabold">{{ __('Two-factor authentication') }}</h3>
                <p class="text-base-content/60">{{ __('Manage your two-factor authentication settings') }}</p>

                <div class="mx-auto mt-4 flex w-full flex-col space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p>{{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}</p>

                            <div class="flex justify-start">
                                <x-button :label="__('Disable 2FA')" class="btn-error" wire:click="disable" spinner="disable" />
                            </div>

                            <livewire:settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="text-base-content/60">{{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}</p>

                            <x-button :label="__('Enable 2FA')" class="btn-primary" wire:click="enable" spinner="enable" />
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- 2FA setup modal --}}
        @if ($canManageTwoFactor)
            <x-modal wire:model="showModal" x-on:close="$wire.closeModal()" box-class="max-w-md">
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="flex size-14 items-center justify-center rounded-full bg-primary-soft text-primary">
                            <x-icon name="o-qr-code" class="size-7" />
                        </div>
                        <div class="space-y-2 text-center">
                            <h3 class="text-lg font-extrabold">{{ $this->modalConfig['title'] }}</h3>
                            <p class="text-base-content/60">{{ $this->modalConfig['description'] }}</p>
                        </div>
                    </div>

                    @if ($showVerificationStep)
                        <div class="space-y-6">
                            <div class="flex justify-center" x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
                                <x-pin wire:model="code" size="6" />
                            </div>

                            <div class="flex items-center gap-3">
                                <x-button :label="__('Back')" class="btn-outline flex-1" wire:click="resetVerification" />
                                <x-button :label="__('Confirm')" class="btn-primary flex-1" wire:click="confirmTwoFactor" x-bind:disabled="$wire.code.length < 6" />
                            </div>
                        </div>
                    @else
                        @error('setupData')
                            <x-alert :title="$message" icon="o-x-circle" class="alert-error" />
                        @enderror

                        <div class="flex justify-center">
                            <div class="relative aspect-square w-64 overflow-hidden rounded-box border border-base-300">
                                @empty($qrCodeSvg)
                                    <div class="absolute inset-0 flex animate-pulse items-center justify-center bg-base-200">
                                        <span class="loading loading-spinner"></span>
                                    </div>
                                @else
                                    <div class="flex h-full items-center justify-center p-4">
                                        <div class="rounded bg-white p-3 dark:brightness-150 dark:invert">
                                            {!! $qrCodeSvg !!}
                                        </div>
                                    </div>
                                @endempty
                            </div>
                        </div>

                        <x-button
                            :label="$this->modalConfig['buttonText']"
                            class="btn-primary w-full"
                            wire:click="showVerificationIfNecessary"
                            :disabled="$errors->has('setupData')"
                        />

                        <div class="space-y-4">
                            <div class="relative flex w-full items-center justify-center">
                                <div class="absolute inset-0 top-1/2 h-px w-full bg-base-300"></div>
                                <span class="relative bg-base-100 px-2 text-sm text-base-content/60">{{ __('or, enter the code manually') }}</span>
                            </div>

                            <div
                                class="flex items-center space-x-2"
                                x-data="{
                                    copied: false,
                                    async copy() {
                                        try {
                                            await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 1500);
                                        } catch (e) {
                                            console.warn('Could not copy to clipboard');
                                        }
                                    }
                                }"
                            >
                                <div class="flex w-full items-stretch rounded-box border border-base-300">
                                    @empty($manualSetupKey)
                                        <div class="flex w-full items-center justify-center bg-base-200 p-3">
                                            <span class="loading loading-spinner loading-sm"></span>
                                        </div>
                                    @else
                                        <input type="text" readonly value="{{ $manualSetupKey }}" class="w-full bg-transparent p-3 outline-none" />
                                        <button type="button" @click="copy()" class="cursor-pointer border-s border-base-300 px-3">
                                            <x-icon name="o-document-duplicate" x-show="!copied" />
                                            <x-icon name="s-check" x-show="copied" x-cloak class="text-success" />
                                        </button>
                                    @endempty
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </x-modal>
        @endif

        {{-- Passkeys --}}
        @if ($canManagePasskeys)
            <section class="mt-12">
                <h3 class="text-lg font-extrabold">{{ __('Passkeys') }}</h3>
                <p class="text-base-content/60">{{ __('Manage your passkeys for passwordless sign-in') }}</p>

                <div class="mx-auto mt-6 flex w-full flex-col space-y-6 text-sm" wire:cloak>
                    <div class="overflow-hidden rounded-box border border-base-300">
                        @forelse ($passkeys as $passkey)
                            <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-base-300' : '' }}">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-box bg-base-200">
                                        <x-icon name="o-key" class="size-5 text-base-content/50" />
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2.5">
                                            <p class="font-semibold tracking-tight">{{ $passkey['name'] }}</p>
                                            @if ($passkey['authenticator'])
                                                <span class="badge badge-soft badge-sm">{{ $passkey['authenticator'] }}</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-base-content/50">
                                            {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                            @if ($passkey['last_used_at_diff'])
                                                <span class="mx-1 opacity-50">/</span>
                                                {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <x-button icon="o-trash" class="btn-ghost btn-sm btn-square text-error" wire:click="confirmDelete({{ $passkey['id'] }})" />
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-box bg-base-200">
                                    <x-icon name="o-key" class="size-7 text-base-content/40" />
                                </div>
                                <p class="font-semibold">{{ __('No passkeys yet') }}</p>
                                <p class="mt-1 text-base-content/60">{{ __('Add a passkey to sign in without a password') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <x-passkey-registration />
                </div>
            </section>
        @endif
    </x-settings.layout>

    {{-- Delete passkey modal --}}
    <x-modal wire:model="showDeleteModal" x-on:close="$wire.closeDeleteModal()" box-class="max-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <h3 class="text-lg font-extrabold">{{ __('Remove passkey') }}</h3>
                <p class="text-base-content/70">
                    {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <x-button :label="__('Cancel')" class="btn-outline" wire:click="closeDeleteModal" />
                <x-button :label="__('Remove passkey')" class="btn-error" wire:click="deletePasskey" spinner="deletePasskey" />
            </div>
        </div>
    </x-modal>
</section>
