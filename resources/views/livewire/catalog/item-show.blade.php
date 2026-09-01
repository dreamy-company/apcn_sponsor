<section class="w-full">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ $item->name }}</h1>
                <p class="mt-1 text-base-content/60">{{ $item->type ?? __('Uncategorised item') }}</p>
            </div>
            <div class="flex gap-3">
                <x-button :label="__('Back')" icon="o-arrow-left" :link="route('catalog.items.index')" class="btn-ghost" />
                <x-button :label="__('Edit')" icon="o-pencil" :link="route('catalog.items.edit', $item)" class="btn-ghost" />
            </div>
        </div>

        {{-- Quota --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Default Price') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">
                    {{ $item->default_price !== null ? 'Rp '.number_format((float) $item->default_price, 0, ',', '.') : '—' }}
                </div>
                <p class="mt-1 text-sm text-base-content/60">{{ __('Add-on rate card') }}</p>
            </x-card>
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Quota') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">
                    {{ $taken }} / {{ $item->quota ?? '∞' }}
                </div>
                <p class="mt-1 text-sm text-base-content/60">
                    @if ($item->quota === null)
                        {{ __('Unlimited') }}
                    @elseif ($taken >= $item->quota)
                        <span class="text-error">{{ __('Full') }}</span>
                    @else
                        {{ __(':n slots remaining', ['n' => $item->quota - $taken]) }}
                    @endif
                </p>
            </x-card>
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Material') }}</h3>
                <div class="mt-2">
                    <span class="badge badge-soft {{ $item->requires_material ? 'badge-warning' : 'badge-ghost' }}">
                        {{ $item->requires_material ? __('Required') : __('None') }}
                    </span>
                </div>
            </x-card>
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('In Deals') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">{{ $deals->count() }}</div>
                <p class="mt-1 text-sm text-base-content/60">{{ __('draft + finalized') }}</p>
            </x-card>
        </div>

        {{-- Sponsors who took it --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Sponsors who took this item') }}</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Sponsor') }}</th>
                            <th>{{ __('Deal #') }}</th>
                            <th>{{ __('Type') }}</th>
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
                                <td>
                                    <span class="badge badge-soft {{ $deal->pivot->is_addon ? 'badge-info' : 'badge-ghost' }}">
                                        {{ $deal->pivot->is_addon ? __('Add-on') : __('Package item') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft {{ $deal->status === \App\Enums\DealStatus::Finalized ? 'badge-success' : 'badge-ghost' }}">
                                        {{ $deal->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-base-content/50">{{ __('No sponsors have taken this item yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</section>
