<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ __('Packages') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('Sponsorship tiers composed of catalog items.') }}</p>
            </div>
            <x-button :label="__('New Package')" icon="o-plus" :link="route('catalog.packages.create')" class="btn-primary" />
        </div>

        <x-card>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Default Price') }}</th>
                            <th>{{ __('Items') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($packages as $package)
                            <tr wire:key="{{ $package->id }}">
                                <td class="font-semibold">{{ $package->name }}</td>
                                <td>Rp {{ number_format((float) $package->default_price, 0, ',', '.') }}</td>
                                <td>{{ $package->items_count }} {{ __('items') }}</td>
                                <td>
                                    <div class="flex gap-1">
                                        <x-button icon="o-pencil" :link="route('catalog.packages.edit', $package)" class="btn-ghost btn-sm btn-square" />
                                        <x-button
                                            icon="o-trash"
                                            wire:click="delete({{ $package->id }})"
                                            wire:confirm="{{ __('Delete this package?') }}"
                                            class="btn-ghost btn-sm btn-square text-error"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-base-content/50">{{ __('No packages yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</section>
