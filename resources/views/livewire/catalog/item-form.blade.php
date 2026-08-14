<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ $item ? __('Edit Item') : __('New Item') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('An item is a single sponsorship deliverable.') }}</p>
            </div>
            <x-button :label="__('Back')" icon="o-arrow-left" :link="route('catalog.items.index')" class="btn-ghost" />
        </div>

        @if (session('status'))
            <x-alert :title="session('status')" icon="o-check-circle" class="alert-success" />
        @endif

        <x-card>
            <form wire:submit="save" class="space-y-4">
                <x-input :label="__('Name')" wire:model="name" :placeholder="__('Booth 3x3m')" />

                <x-input
                    :label="__('Type')"
                    :hint="__('optional')"
                    wire:model="type"
                    :placeholder="__('booth, symposium, naming, advertising, digital...')"
                />

                <div>
                    <div class="flex items-center justify-between gap-4 rounded-box border border-base-300 p-3">
                        <div>
                            <div class="text-sm font-semibold">{{ __('Requires Material') }}</div>
                            <div class="text-xs text-base-content/50">{{ __('Generates a material checklist item when a deal is finalized.') }}</div>
                        </div>
                        <x-toggle wire:model="requiresMaterial" />
                    </div>
                    @error('requiresMaterial') <div class="mt-1 text-error">{{ $message }}</div> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <x-button :label="__('Cancel')" :link="route('catalog.items.index')" class="btn-ghost" />
                    <x-button :label="$item ? __('Save Changes') : __('Create Item')" type="submit" class="btn-primary" spinner="save" />
                </div>
            </form>
        </x-card>
    </div>
</section>
