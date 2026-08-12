<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Deals') }}</flux:heading>
                <flux:text class="mt-1">{{ __('All sponsorship deals, centrally tracked.') }}</flux:text>
            </div>
            @if (auth()->user()->isJ4u())
                <flux:button href="{{ route('deals.create') }}" wire:navigate icon="plus">
                    {{ __('New Deal') }}
                </flux:button>
            @endif
        </div>

        <flux:card class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search sponsor...') }}"
                    class="max-w-sm"
                />
                <flux:select wire:model.live="status" class="w-48">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Deal #') }}</flux:table.column>
                    <flux:table.column>{{ __('Sponsor') }}</flux:table.column>
                    <flux:table.column>{{ __('Doctor') }}</flux:table.column>
                    <flux:table.column>{{ __('Package') }}</flux:table.column>
                    <flux:table.column>{{ __('Final Price') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($deals as $deal)
                        <flux:table.row :key="$deal->id">
                            <flux:table.cell class="font-medium">
                                <flux:link href="{{ route('deals.show', $deal) }}" wire:navigate>
                                    {{ $deal->deal_number }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $deal->sponsor->company_name }}</flux:table.cell>
                            <flux:table.cell>{{ $deal->doctor->name }}</flux:table.cell>
                            <flux:table.cell>{{ $deal->package?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge
                                    :color="$deal->status === \App\Enums\DealStatus::Finalized ? 'emerald' : 'zinc'"
                                    inset="top bottom"
                                >
                                    {{ $deal->status->label() }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-neutral-500">
                                {{ __('No deals found.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</section>
