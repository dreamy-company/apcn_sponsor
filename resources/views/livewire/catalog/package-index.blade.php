<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Packages') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Sponsorship tiers composed of catalog items.') }}</flux:text>
            </div>
            <flux:button href="{{ route('catalog.packages.create') }}" wire:navigate icon="plus">
                {{ __('New Package') }}
            </flux:button>
        </div>

        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Default Price') }}</flux:table.column>
                    <flux:table.column>{{ __('Items') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($packages as $package)
                        <flux:table.row :key="$package->id">
                            <flux:table.cell class="font-medium">{{ $package->name }}</flux:table.cell>
                            <flux:table.cell>Rp {{ number_format((float) $package->default_price, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>{{ $package->items_count }} {{ __('items') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="xs" variant="subtle" icon="pencil" :href="route('catalog.packages.edit', $package)" wire:navigate />
                                    <flux:button
                                        size="xs"
                                        variant="subtle"
                                        icon="trash"
                                        wire:click="delete({{ $package->id }})"
                                        wire:confirm="{{ __('Delete this package?') }}"
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-neutral-500">{{ __('No packages yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</section>
