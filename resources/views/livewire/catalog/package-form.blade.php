<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $package ? __('Edit Package') : __('New Package') }}</flux:heading>
                <flux:text class="mt-1">{{ __('A package (tier) bundles several items at a default price.') }}</flux:text>
            </div>
            <flux:button variant="ghost" href="{{ route('catalog.packages.index') }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
        @endif

        <flux:card>
            <form wire:submit="save" class="space-y-4">
                <flux:fieldset>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Name') }}</flux:label>
                            <flux:input wire:model="name" placeholder="{{ __('Diamond') }}" />
                            <flux:error name="name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Default Price (IDR)') }}</flux:label>
                            <flux:input wire:model="defaultPrice" type="number" min="0" step="0.01" placeholder="0" />
                            <flux:error name="defaultPrice" />
                        </flux:field>
                    </div>

                    <flux:separator class="my-4" />

                    <flux:field>
                        <flux:label>{{ __('Items in Package') }}</flux:label>

                        <div class="space-y-2">
                            @forelse ($items as $item)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                    <flux:checkbox wire:model="selectedItems" value="{{ $item->id }}" />
                                    <span class="text-sm">{{ $item->name }}</span>
                                </label>
                            @empty
                                <flux:text class="text-neutral-500">{{ __('No items in the catalog yet.') }}</flux:text>
                            @endforelse
                        </div>

                        <flux:error name="selectedItems" />
                    </flux:field>
                </flux:fieldset>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" href="{{ route('catalog.packages.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ $package ? __('Save Changes') : __('Create Package') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</section>
