<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ $package ? __('Edit Package') : __('New Package') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('A package (tier) bundles several items at a default price.') }}</p>
            </div>
            <x-button :label="__('Back')" icon="o-arrow-left" :link="route('catalog.packages.index')" class="btn-ghost" />
        </div>

        @if (session('status'))
            <x-alert :title="session('status')" icon="o-check-circle" class="alert-success" />
        @endif

        <x-card>
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-input :label="__('Name')" wire:model="name" :placeholder="__('Diamond')" />
                    <x-input :label="__('Default Price (IDR)')" wire:model="defaultPrice" type="number" min="0" step="0.01" placeholder="0" prefix="Rp" />
                    <x-input :label="__('Quota')" :hint="__('Blank = unlimited')" wire:model="quota" type="number" min="0" :placeholder="__('Unlimited')" />
                </div>

                <hr class="border-base-300">

                <div>
                    <label class="fieldset-label mb-2 block text-sm font-semibold">{{ __('Items in Package') }}</label>

                    <div class="space-y-2">
                        @forelse ($items as $item)
                            <label class="flex cursor-pointer items-center gap-3 rounded-box border border-base-300 p-3">
                                <x-checkbox wire:model="selectedItems" value="{{ $item->id }}" />
                                <span class="text-sm">{{ $item->name }}</span>
                            </label>
                        @empty
                            <p class="text-base-content/50">{{ __('No items in the catalog yet.') }}</p>
                        @endforelse
                    </div>

                    @error('selectedItems') <div class="mt-1 text-error">{{ $message }}</div> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <x-button :label="__('Cancel')" :link="route('catalog.packages.index')" class="btn-ghost" />
                    <x-button :label="$package ? __('Save Changes') : __('Create Package')" type="submit" class="btn-primary" spinner="save" />
                </div>
            </form>
        </x-card>
    </div>
</section>
