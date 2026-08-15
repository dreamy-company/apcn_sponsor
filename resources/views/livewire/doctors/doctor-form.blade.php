<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ $doctor ? __('Edit Doctor') : __('New Doctor') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('A doctor is a non-login initiator with a public read-only link.') }}</p>
            </div>
            <x-button :label="__('Back')" icon="o-arrow-left" :link="route('doctors.index')" class="btn-ghost" />
        </div>

        @if (session('status'))
            <x-alert :title="session('status')" icon="o-check-circle" class="alert-success" />
        @endif

        <x-card>
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input :label="__('Name')" wire:model="name" :placeholder="__('Dr. Budi Santoso')" />
                    <x-input :label="__('Contact')" :hint="__('phone / WhatsApp (optional)')" wire:model="phone" placeholder="+62 812 3456 7890" />
                </div>

                @if ($doctor && $doctor->publicUrl())
                    <div>
                        <label class="fieldset-label mb-1 block text-sm font-semibold">{{ __('Public link') }}</label>
                        <div class="flex items-stretch rounded-box border border-base-300">
                            <input type="text" readonly value="{{ $doctor->publicUrl() }}" class="w-full bg-transparent p-3 text-sm outline-none" />
                            <button type="button" class="cursor-pointer border-s border-base-300 px-3"
                                    x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText('{{ $doctor->publicUrl() }}'); copied = true; setTimeout(() => copied = false, 1500)">
                                <span x-show="!copied"><x-icon name="o-document-duplicate" class="h-4 w-4" /></span>
                                <span x-show="copied" x-cloak class="text-success">{{ __('Copied!') }}</span>
                            </button>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <x-button :label="__('Cancel')" :link="route('doctors.index')" class="btn-ghost" />
                    <x-button :label="$doctor ? __('Save Changes') : __('Create Doctor')" type="submit" class="btn-primary" spinner="save" />
                </div>
            </form>
        </x-card>
    </div>
</section>
