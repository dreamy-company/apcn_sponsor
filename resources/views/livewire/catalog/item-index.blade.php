<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Items') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Sponsorship deliverables from the prospectus.') }}</flux:text>
            </div>
            <flux:button href="{{ route('catalog.items.create') }}" wire:navigate icon="plus">
                {{ __('New Item') }}
            </flux:button>
        </div>

        <flux:card class="space-y-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search items...') }}"
                class="max-w-sm"
            />

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Material') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell class="font-medium">{{ $item->name }}</flux:table.cell>
                            <flux:table.cell>{{ $item->type ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$item->requires_material ? 'amber' : 'zinc'" inset="top bottom">
                                    {{ $item->requires_material ? __('Required') : __('None') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="xs" variant="subtle" icon="pencil" :href="route('catalog.items.edit', $item)" wire:navigate />
                                    <flux:button
                                        size="xs"
                                        variant="subtle"
                                        icon="trash"
                                        wire:click="delete({{ $item->id }})"
                                        wire:confirm="{{ __('Delete this item?') }}"
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-neutral-500">{{ __('No items yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</section>
