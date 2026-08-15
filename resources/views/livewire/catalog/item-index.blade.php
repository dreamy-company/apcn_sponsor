<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ __('Items') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('Sponsorship deliverables from the prospectus.') }}</p>
            </div>
            <x-button :label="__('New Item')" icon="o-plus" :link="route('catalog.items.create')" class="btn-primary" />
        </div>

        <x-card>
            <x-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                :placeholder="__('Search items...')"
                class="max-w-sm"
            />

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Quota') }}</th>
                            <th>{{ __('Material') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr wire:key="{{ $item->id }}">
                                <td class="font-semibold">
                                    <a href="{{ route('catalog.items.show', $item) }}" class="link link-primary" wire:navigate>{{ $item->name }}</a>
                                </td>
                                <td>{{ $item->type ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-soft {{ $item->quota !== null && $item->taken_count >= $item->quota ? 'badge-error' : 'badge-ghost' }}">
                                        {{ $item->taken_count }} / {{ $item->quota ?? '∞' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft {{ $item->requires_material ? 'badge-warning' : 'badge-ghost' }}">
                                        {{ $item->requires_material ? __('Required') : __('None') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <x-button icon="o-pencil" :link="route('catalog.items.edit', $item)" class="btn-ghost btn-sm btn-square" />
                                        <x-button
                                            icon="o-trash"
                                            wire:click="delete({{ $item->id }})"
                                            wire:confirm="{{ __('Delete this item?') }}"
                                            class="btn-ghost btn-sm btn-square text-error"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-base-content/50">{{ __('No items yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</section>
