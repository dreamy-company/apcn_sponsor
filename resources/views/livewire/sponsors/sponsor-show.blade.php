<section class="w-full">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ $sponsor->company_name }}</h1>
                    <x-tier-badge :package="$topPackage" size="lg" />
                </div>
                <p class="mt-1 text-base-content/60">
                    {{ __('PIC') }}: <span class="font-semibold">{{ $sponsor->pic_name }}</span> · {{ $sponsor->pic_contact }}
                </p>
            </div>
            <x-button :label="__('Back to sponsors')" icon="o-arrow-left" :link="route('sponsors.index')" class="btn-ghost" />
        </div>

        {{-- Stats --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Deals') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">{{ $deals->count() }}</div>
            </x-card>
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Total Value') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">Rp {{ number_format((float) $totalValue, 0, ',', '.') }}</div>
            </x-card>
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Items Taken') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">{{ $items->count() }}</div>
            </x-card>
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Assets') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">{{ $assetsCount }}</div>
            </x-card>
        </div>

        {{-- Deals --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Deals') }}</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Deal #') }}</th>
                            <th>{{ __('Package') }}</th>
                            <th>{{ __('Doctor') }}</th>
                            <th>{{ __('Final Price') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deals as $deal)
                            <tr wire:key="deal-{{ $deal->id }}">
                                <td class="font-semibold">
                                    <a href="{{ route('deals.show', $deal) }}" class="link link-primary" wire:navigate>{{ $deal->deal_number }}</a>
                                </td>
                                <td><x-tier-badge :package="$deal->package" /></td>
                                <td>{{ $deal->doctor?->name ?? '—' }}</td>
                                <td>Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge badge-soft {{ $deal->status === \App\Enums\DealStatus::Finalized ? 'badge-success' : 'badge-ghost' }}">
                                        {{ $deal->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-base-content/50">{{ __('No deals yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Items across all deals --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Items Taken') }}</h2>
            <p class="mt-1 text-sm text-base-content/60">{{ __('Distinct sponsorship items across all of this company\'s deals.') }}</p>

            <div class="mt-3 flex flex-wrap gap-1.5">
                @forelse ($items as $item)
                    <a href="{{ route('catalog.items.show', $item) }}" wire:navigate class="badge badge-soft badge-ghost">{{ $item->name }}</a>
                @empty
                    <span class="text-sm text-base-content/50">{{ __('No items taken.') }}</span>
                @endforelse
            </div>
        </x-card>

        {{-- Assets per deal --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Assets') }}</h2>
            <p class="mt-1 text-sm text-base-content/60">{{ __('Files attached per deal. Max 50MB each.') }}</p>

            <div class="mt-4 space-y-6">
                @forelse ($deals as $deal)
                    <div wire:key="deal-assets-{{ $deal->id }}" class="rounded-box border border-base-300 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('deals.show', $deal) }}" wire:navigate class="font-semibold link link-primary">{{ $deal->deal_number }}</a>
                                @if ($deal->assets->isNotEmpty())
                                    <x-button :label="__('Download all')" icon="o-arrow-down-tray" wire:click="downloadAll({{ $deal->id }})" spinner="downloadAll({{ $deal->id }})" class="btn-outline btn-xs" />
                                @endif
                            </div>

                            @if (auth()->user()->isJ4u())
                                <form wire:submit="uploadAssets({{ $deal->id }})" class="flex flex-wrap items-end gap-2">
                                    <x-file wire:model="dealAssets.{{ $deal->id }}" multiple class="max-w-xs" />
                                    <x-button :label="__('Upload')" icon="o-arrow-up-tray" type="submit" class="btn-primary btn-sm" spinner="uploadAssets({{ $deal->id }})" />
                                </form>
                            @endif
                        </div>
                        @error("dealAssets.{$deal->id}.*") <div class="mt-1 text-error">{{ $message }}</div> @enderror

                        @if (! empty($dealAssets[$deal->id]))
                            <div class="mt-3 space-y-2 rounded-box border border-base-300 p-3">
                                <p class="text-xs font-semibold text-base-content/60">{{ __('Name each file (optional)') }}</p>
                                @foreach ($dealAssets[$deal->id] as $i => $file)
                                    <div class="flex items-center gap-3" wire:key="new-asset-{{ $deal->id }}-{{ $i }}">
                                        <x-input wire:model="dealAssetNames.{{ $deal->id }}.{{ $i }}" :placeholder="$file->getClientOriginalName()" class="grow" />
                                        <span class="shrink-0 text-xs text-base-content/50">{{ $file->getClientOriginalName() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 space-y-1">
                            @forelse ($deal->assets as $asset)
                                <div wire:key="asset-{{ $asset->id }}" class="flex items-center justify-between gap-3 rounded-box bg-base-200 px-3 py-2">
                                    <button type="button" wire:click="downloadAsset({{ $asset->id }})" class="link link-primary truncate text-start text-sm font-semibold">{{ $asset->displayName() }}</button>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span class="text-xs text-base-content/50">{{ $asset->humanSize() }}</span>
                                        <x-button icon="o-arrow-down-tray" wire:click="downloadAsset({{ $asset->id }})" class="btn-ghost btn-xs btn-square" />
                                        @if (auth()->user()->isJ4u())
                                            <x-button
                                                icon="o-trash"
                                                wire:click="deleteAsset({{ $asset->id }})"
                                                wire:confirm="{{ __('Remove this asset?') }}"
                                                class="btn-ghost btn-xs btn-square text-error"
                                            />
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-base-content/50">{{ __('No assets for this deal.') }}</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <p class="text-base-content/50">{{ __('No deals to attach assets to.') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>
</section>
