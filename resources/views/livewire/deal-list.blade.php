<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ __('Deals') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('All sponsorship deals, centrally tracked.') }}</p>
            </div>
            @if (auth()->user()->isJ4u())
                <x-button :label="__('New Deal')" icon="o-plus" :link="route('deals.create')" class="btn-primary" />
            @endif
        </div>

        <x-card>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-input
                    wire:model.live.debounce.300ms="search"
                    icon="o-magnifying-glass"
                    :placeholder="__('Search sponsor...')"
                    class="max-w-sm"
                />
                <x-select
                    wire:model.live="status"
                    class="w-48"
                    :options="collect($statuses)->map(fn ($s) => ['id' => $s->value, 'name' => $s->label()])"
                    option-value="id"
                    option-label="name"
                    :placeholder="__('All statuses')"
                />
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Deal #') }}</th>
                            <th>{{ __('Sponsor') }}</th>
                            <th>{{ __('Doctor') }}</th>
                            <th>{{ __('Level') }}</th>
                            <th>{{ __('Final Price') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deals as $deal)
                            <tr wire:key="{{ $deal->id }}">
                                <td class="font-semibold">
                                    <a href="{{ route('deals.show', $deal) }}" wire:navigate class="link link-primary">{{ $deal->deal_number }}</a>
                                </td>
                                <td>{{ $deal->sponsor->company_name }}</td>
                                <td>{{ $deal->doctor->name }}</td>
                                <td><x-tier-badge :package="$deal->package" /></td>
                                <td>Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge badge-soft {{ $deal->status === \App\Enums\DealStatus::Finalized ? 'badge-success' : 'badge-ghost' }}">
                                        {{ $deal->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-base-content/50">{{ __('No deals found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</section>
