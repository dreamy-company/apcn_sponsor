<section class="w-full">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ $package->name }}</h1>
                <p class="mt-1 text-base-content/60">Rp {{ number_format((float) $package->default_price, 0, ',', '.') }}</p>
            </div>
            <div class="flex gap-3">
                <x-button :label="__('Back')" icon="o-arrow-left" :link="route('catalog.packages.index')" class="btn-ghost" />
                <x-button :label="__('Edit')" icon="o-pencil" :link="route('catalog.packages.edit', $package)" class="btn-ghost" />
            </div>
        </div>

        {{-- Quota + items --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Quota') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">{{ $taken }} / {{ $package->quota ?? '∞' }}</div>
                <p class="mt-1 text-sm text-base-content/60">
                    @if ($package->quota === null)
                        {{ __('Unlimited') }}
                    @elseif ($taken >= $package->quota)
                        <span class="text-error">{{ __('Full') }}</span>
                    @else
                        {{ __(':n slots remaining', ['n' => $package->quota - $taken]) }}
                    @endif
                </p>
            </x-card>
            <x-card class="sm:col-span-2">
                <h3 class="eyebrow text-base-content/50">{{ __('Items in Package') }}</h3>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @forelse ($package->items as $item)
                        <a href="{{ route('catalog.items.show', $item) }}" wire:navigate class="badge badge-soft badge-ghost">{{ $item->name }}</a>
                    @empty
                        <span class="text-sm text-base-content/50">{{ __('No items in this package.') }}</span>
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Sponsors who took it --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Sponsors on this package') }}</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Sponsor') }}</th>
                            <th>{{ __('Deal #') }}</th>
                            <th>{{ __('Final Price') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deals as $deal)
                            <tr wire:key="deal-{{ $deal->id }}">
                                <td class="font-semibold">
                                    <a href="{{ route('sponsors.show', $deal->sponsor) }}" class="link link-primary" wire:navigate>{{ $deal->sponsor->company_name }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('deals.show', $deal) }}" class="link" wire:navigate>{{ $deal->deal_number }}</a>
                                </td>
                                <td>Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge badge-soft {{ $deal->status === \App\Enums\DealStatus::Finalized ? 'badge-success' : 'badge-ghost' }}">
                                        {{ $deal->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-base-content/50">{{ __('No sponsors on this package yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</section>
