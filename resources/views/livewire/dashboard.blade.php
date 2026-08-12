<section class="w-full">
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:text class="mt-1">
                @if (auth()->user()->isJ4u())
                    {{ __('Sponsorship overview for APCN 2027.') }}
                @else
                    {{ __('Your sponsorship deals at a glance.') }}
                @endif
            </flux:text>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <flux:card>
                <flux:heading size="sm">{{ __('Total Committed') }}</flux:heading>
                <flux:heading size="2xl" class="mt-2">Rp {{ number_format((float) $summary['totalCommitted'], 0, ',', '.') }}</flux:heading>
                <flux:text class="mt-1">{{ $summary['finalizedCount'] }} {{ __('finalized deals') }}</flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">{{ __('Deals') }}</flux:heading>
                <flux:heading size="2xl" class="mt-2">{{ $summary['dealsCount'] }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $summary['draftCount'] }} {{ __('draft') }} · {{ $summary['finalizedCount'] }} {{ __('finalized') }}
                </flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">{{ __('Payments') }}</flux:heading>
                <flux:heading size="2xl" class="mt-2">Rp {{ number_format((float) $summary['paidAmount'], 0, ',', '.') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Outstanding:') }} Rp {{ number_format((float) $summary['outstandingAmount'], 0, ',', '.') }}</flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">{{ __('Materials') }}</flux:heading>
                <flux:heading size="2xl" class="mt-2">
                    {{ $summary['materialReceived'] }}
                    <span class="text-base font-normal text-neutral-500">/ {{ $summary['materialTotal'] }}</span>
                </flux:heading>
                <flux:text class="mt-1">{{ __('received of all required materials') }}</flux:text>
            </flux:card>
        </div>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Recent Deals') }}</flux:heading>
                <flux:link href="{{ route('deals.index') }}" wire:navigate>{{ __('View all') }} →</flux:link>
            </div>

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Deal #') }}</flux:table.column>
                    <flux:table.column>{{ __('Sponsor') }}</flux:table.column>
                    <flux:table.column>{{ __('Doctor') }}</flux:table.column>
                    <flux:table.column>{{ __('Package') }}</flux:table.column>
                    <flux:table.column>{{ __('Final Price') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($recentDeals as $deal)
                        <flux:table.row :key="$deal->id">
                            <flux:table.cell class="font-medium">
                                <flux:link href="{{ route('deals.show', $deal) }}" wire:navigate>{{ $deal->deal_number }}</flux:link>
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
                                {{ __('No deals yet.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</section>
