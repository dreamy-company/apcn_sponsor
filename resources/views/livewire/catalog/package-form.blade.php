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
                    <x-money-input :label="__('Default Price (IDR)')" wire:model="defaultPriceIdr" currency="IDR" />
                    <x-money-input :label="__('Default Price (USD)')" wire:model="defaultPriceUsd" currency="USD"
                                   :hint="__('Used when the deal is transacted in USD.')" />
                    <x-input :label="__('Quota')" :hint="__('Blank = unlimited')" wire:model="quota" type="number" min="0" :placeholder="__('Unlimited')" />
                </div>

                <hr class="border-base-300">

                <div>
                    <label class="fieldset-label mb-2 block text-sm font-semibold">{{ __('Items in Package') }}</label>
                    <p class="mb-2 text-xs text-base-content/50">
                        {{ __('Set how many units the tier includes — e.g. 5 booths, 15 complimentary registrations.') }}
                    </p>

                    <div class="space-y-2">
                        @forelse ($items as $item)
                            @php $isSelected = in_array((string) $item->id, array_map('strval', $selectedItems), true); @endphp
                            <label wire:key="pkg-item-{{ $item->id }}"
                                   class="flex cursor-pointer items-center gap-3 rounded-box border border-base-300 p-3">
                                <x-checkbox wire:model.live="selectedItems" value="{{ $item->id }}" />
                                <span class="grow text-sm">{{ $item->name }}</span>
                                @if ($isSelected)
                                    <span class="flex items-center gap-2">
                                        <span class="text-xs text-base-content/50">{{ __('Qty') }}</span>
                                        <x-input
                                            wire:model="itemQuantities.{{ $item->id }}"
                                            type="number" min="1" step="1" placeholder="1"
                                            class="w-20"
                                            @click.stop
                                        />
                                    </span>
                                @endif
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
