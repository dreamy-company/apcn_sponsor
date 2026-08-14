<section class="w-full">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('Dashboard') }}</h1>
                    <flux:badge color="teal" inset="top bottom">{{ __('APCN 2027') }}</flux:badge>
                </div>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if (auth()->user()->isJ4u())
                        {{ __('Sponsorship overview for APCN 2027.') }}
                    @else
                        {{ __('Your sponsorship deals at a glance.') }}
                    @endif
                </p>
            </div>

            @if (auth()->user()->isJ4u())
                <flux:button variant="primary" icon="plus" :href="route('deals.create')" wire:navigate class="shrink-0">
                    {{ __('New Deal') }}
                </flux:button>
            @endif
        </div>

        {{-- Indicator cards --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Total committed (gold) --}}
            <div class="rounded-2xl border-2 border-amber-400/60 bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-amber-400/25 dark:bg-zinc-800">
                <div class="flex size-9 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                    <flux:icon name="banknotes" class="size-4" />
                </div>
                <div class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Rp {{ number_format((float) $summary['totalCommitted'], 0, ',', '.') }}
                </div>
                <p class="mt-1 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Total Committed') }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ $summary['finalizedCount'] }} {{ __('finalized deals') }}</p>
            </div>

            {{-- Active deals (blue) --}}
            <div class="rounded-2xl border-2 border-blue-400/60 bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-blue-400/25 dark:bg-zinc-800">
                <div class="flex size-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                    <flux:icon name="briefcase" class="size-4" />
                </div>
                <div class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    {{ $summary['dealsCount'] }}
                </div>
                <p class="mt-1 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Active Deals') }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ $summary['draftCount'] }} {{ __('drafts') }} · {{ $summary['finalizedCount'] }} {{ __('finalized') }}</p>
            </div>

            {{-- Payments received (green) --}}
            <div class="rounded-2xl border-2 border-emerald-400/60 bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-emerald-400/25 dark:bg-zinc-800">
                <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <flux:icon name="check-circle" class="size-4" />
                </div>
                <div class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Rp {{ number_format((float) $summary['paidAmount'], 0, ',', '.') }}
                </div>
                <p class="mt-1 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Payments Received') }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ __('received from sponsors') }}</p>
            </div>

            {{-- Outstanding (red) --}}
            <div class="rounded-2xl border-2 border-rose-400/60 bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-rose-400/25 dark:bg-zinc-800">
                <div class="flex size-9 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                    <flux:icon name="clock" class="size-4" />
                </div>
                <div class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Rp {{ number_format((float) $summary['outstandingAmount'], 0, ',', '.') }}
                </div>
                <p class="mt-1 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Outstanding') }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ __('awaiting payment') }}</p>
            </div>

            {{-- Materials (teal) --}}
            <div class="rounded-2xl border-2 border-teal-400/60 bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-teal-400/25 dark:bg-zinc-800">
                <div class="flex size-9 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-500/15 dark:text-teal-400">
                    <flux:icon name="cube" class="size-4" />
                </div>
                <div class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    {{ $summary['materialReceived'] }}<span class="text-base font-normal text-zinc-400 dark:text-zinc-500"> / {{ $summary['materialTotal'] }}</span>
                </div>
                <p class="mt-1 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Materials') }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ __('received of all required') }}</p>
            </div>

            {{-- Drafts (indigo) --}}
            <div class="rounded-2xl border-2 border-indigo-400/60 bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-indigo-400/25 dark:bg-zinc-800">
                <div class="flex size-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">
                    <flux:icon name="document-text" class="size-4" />
                </div>
                <div class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    {{ $summary['draftCount'] }}
                </div>
                <p class="mt-1 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Drafts') }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ __('awaiting finalization') }}</p>
            </div>
        </div>

        {{-- Recent deals table --}}
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex flex-col gap-3 border-b border-zinc-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">
                        <flux:icon name="clipboard-document-list" class="size-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Deals') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Latest sponsorship activity') }}</p>
                    </div>
                </div>
                <flux:link href="{{ route('deals.index') }}" wire:navigate class="text-sm font-medium">{{ __('View all') }} →</flux:link>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Deal #') }}</flux:table.column>
                    <flux:table.column>{{ __('Sponsor') }}</flux:table.column>
                    <flux:table.column>{{ __('Doctor') }}</flux:table.column>
                    <flux:table.column>{{ __('Package') }}</flux:table.column>
                    <flux:table.column>{{ __('Final Price') }}</flux:table.column>
                    <flux:table.column>{{ __('Paid') }}</flux:table.column>
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
                                @if ((float) $deal->paid_total > 0)
                                    Rp {{ number_format((float) $deal->paid_total, 0, ',', '.') }}
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                @endif
                            </flux:table.cell>
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
                            <flux:table.cell colspan="7" class="text-center text-neutral-500">
                                {{ __('No deals yet.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</section>
