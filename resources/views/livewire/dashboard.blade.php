<section class="w-full">
    <div class="space-y-6">
        {{-- Gradient hero header --}}
        <div class="bg-gradient-brand rounded-box p-6 text-white shadow-soft-lg">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold tracking-tight">{{ __('Dashboard') }}</h1>
                        <span class="badge border-white/25 bg-white/15 text-white">{{ __('APCN 2027') }}</span>
                    </div>
                    <p class="mt-1 text-sm text-white/70">
                        @if (auth()->user()->isJ4u())
                            {{ __('Sponsorship overview for APCN 2027.') }}
                        @else
                            {{ __('Your sponsorship deals at a glance.') }}
                        @endif
                    </p>
                </div>

                @if (auth()->user()->isJ4u())
                    <a href="{{ route('deals.create') }}" wire:navigate
                       class="btn shrink-0 border-0 bg-gold text-neutral hover:bg-brand-amber">
                        <x-icon name="o-plus" class="h-5 w-5" />
                        {{ __('New Deal') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Indicator cards --}}
        @php
            $cards = [
                ['icon' => 'o-banknotes', 'tone' => 'primary', 'value' => 'Rp '.number_format((float) $summary['totalCommitted'], 0, ',', '.'), 'label' => __('Total Committed'), 'sub' => $summary['finalizedCount'].' '.__('finalized deals')],
                ['icon' => 'o-briefcase', 'tone' => 'info', 'value' => $summary['dealsCount'], 'label' => __('Active Deals'), 'sub' => $summary['draftCount'].' '.__('drafts').' · '.$summary['finalizedCount'].' '.__('finalized')],
                ['icon' => 'o-check-circle', 'tone' => 'success', 'value' => 'Rp '.number_format((float) $summary['paidAmount'], 0, ',', '.'), 'label' => __('Payments Received'), 'sub' => __('received from sponsors')],
                ['icon' => 'o-clock', 'tone' => 'error', 'value' => 'Rp '.number_format((float) $summary['outstandingAmount'], 0, ',', '.'), 'label' => __('Outstanding'), 'sub' => __('awaiting payment')],
                ['icon' => 'o-cube', 'tone' => 'primary', 'value' => $summary['materialReceived'].' / '.$summary['materialTotal'], 'label' => __('Materials'), 'sub' => __('received of all required')],
                ['icon' => 'o-document-text', 'tone' => 'warning', 'value' => $summary['draftCount'], 'label' => __('Drafts'), 'sub' => __('awaiting finalization')],
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $card)
                <div class="card bg-base-100 p-4 transition-shadow hover:shadow-soft-lg">
                    <div @class([
                        'flex size-9 items-center justify-center rounded-selector',
                        'bg-primary-soft text-primary' => $card['tone'] === 'primary',
                        'bg-info-soft text-info' => $card['tone'] === 'info',
                        'bg-success-soft text-success' => $card['tone'] === 'success',
                        'bg-error-soft text-error' => $card['tone'] === 'error',
                        'bg-warning-soft text-warning' => $card['tone'] === 'warning',
                    ])>
                        <x-icon :name="$card['icon']" class="size-4" />
                    </div>
                    <div class="mt-3 text-2xl font-extrabold tracking-tight">{{ $card['value'] }}</div>
                    <p class="mt-1 text-sm font-semibold text-base-content/70">{{ $card['label'] }}</p>
                    <p class="mt-0.5 text-xs text-base-content/40">{{ $card['sub'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Recent deals --}}
        <div class="card overflow-hidden bg-base-100">
            <div class="flex flex-col gap-3 border-b border-base-300 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-selector bg-base-200 text-base-content/60">
                        <x-icon name="o-clipboard-document-list" class="size-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold">{{ __('Recent Deals') }}</h2>
                        <p class="text-xs text-base-content/50">{{ __('Latest sponsorship activity') }}</p>
                    </div>
                </div>
                <a href="{{ route('deals.index') }}" wire:navigate class="link link-primary text-sm font-semibold">{{ __('View all') }} →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Deal #') }}</th>
                            <th>{{ __('Sponsor') }}</th>
                            <th>{{ __('Doctor') }}</th>
                            <th>{{ __('Package') }}</th>
                            <th>{{ __('Final Price') }}</th>
                            <th>{{ __('Paid') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentDeals as $deal)
                            <tr wire:key="{{ $deal->id }}">
                                <td class="font-semibold">
                                    <a href="{{ route('deals.show', $deal) }}" wire:navigate class="link link-primary">{{ $deal->deal_number }}</a>
                                </td>
                                <td>{{ $deal->sponsor->company_name }}</td>
                                <td>{{ $deal->doctor->name }}</td>
                                <td>{{ $deal->package?->name ?? '—' }}</td>
                                <td>Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</td>
                                <td>
                                    @if ((float) $deal->paid_total > 0)
                                        Rp {{ number_format((float) $deal->paid_total, 0, ',', '.') }}
                                    @else
                                        <span class="text-base-content/40">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft {{ $deal->status === \App\Enums\DealStatus::Finalized ? 'badge-success' : 'badge-ghost' }}">
                                        {{ $deal->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-base-content/50">{{ __('No deals yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
