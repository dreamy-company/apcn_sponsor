<div
    class="space-y-6 rounded-box border border-base-300 py-6 shadow-soft"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="space-y-2 px-6">
        <div class="flex items-center gap-2">
            <x-icon name="o-lock-closed" class="size-4" />
            <h3 class="text-lg font-extrabold">{{ __('2FA recovery codes') }}</h3>
        </div>
        <p class="text-base-content/60">
            {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
        </p>
    </div>

    <div class="px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-button
                x-show="!showRecoveryCodes"
                icon="o-eye"
                class="btn-primary"
                :label="__('View recovery codes')"
                @click="showRecoveryCodes = true"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
            />

            <x-button
                x-show="showRecoveryCodes"
                icon="o-eye-slash"
                class="btn-primary"
                :label="__('Hide recovery codes')"
                @click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
            />

            @if (filled($recoveryCodes))
                <x-button
                    x-show="showRecoveryCodes"
                    icon="o-arrow-path"
                    :label="__('Regenerate codes')"
                    wire:click="regenerateRecoveryCodes"
                />
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
            x-bind:aria-hidden="!showRecoveryCodes"
        >
            <div class="mt-3 space-y-3">
                @error('recoveryCodes')
                    <x-alert :title="$message" icon="o-x-circle" class="alert-error" />
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 rounded-box bg-base-200 p-4 font-mono text-sm"
                        role="list"
                        aria-label="{{ __('Recovery codes') }}"
                    >
                        @foreach ($recoveryCodes as $code)
                            <div role="listitem" class="select-text" wire:loading.class="animate-pulse opacity-50">{{ $code }}</div>
                        @endforeach
                    </div>
                    <p class="text-xs text-base-content/60">
                        {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
