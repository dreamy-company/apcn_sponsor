<section class="w-full">
    @if (! $unlocked)
        {{-- Access-code gate --}}
        <div class="mx-auto max-w-md">
            <x-card>
                <div class="text-center">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-primary-soft text-primary">
                        <x-icon name="o-lock-closed" class="size-6" />
                    </div>
                    <h1 class="text-xl font-extrabold tracking-tight">{{ __('Enter access code') }}</h1>
                    <p class="mt-1 text-base-content/60">{{ __('Enter the access code to view the APCN 2027 sponsorship report.') }}</p>
                </div>

                <form wire:submit="unlock" class="mt-5 space-y-4">
                    <x-input wire:model="code" :label="__('Access code')" icon="o-key" autofocus />
                    <x-button :label="__('View report')" type="submit" class="btn-primary w-full" spinner="unlock" />
                </form>
            </x-card>
        </div>
    @else
        @php
            $s = $report['summary'];
            $cards = [
                ['label' => __('Total Committed'), 'value' => 'Rp '.number_format((float) $s['totalCommitted'], 0, ',', '.'), 'sub' => $s['finalizedCount'].' '.__('finalized deals'), 'tone' => 'primary', 'icon' => 'o-banknotes'],
                ['label' => __('Payments Received'), 'value' => 'Rp '.number_format((float) $s['paidAmount'], 0, ',', '.'), 'sub' => __('received from sponsors'), 'tone' => 'success', 'icon' => 'o-check-circle'],
                ['label' => __('Outstanding'), 'value' => 'Rp '.number_format((float) $s['outstandingAmount'], 0, ',', '.'), 'sub' => __('awaiting payment'), 'tone' => 'error', 'icon' => 'o-clock'],
                ['label' => __('Sponsors'), 'value' => $report['sponsorsCount'], 'sub' => $s['dealsCount'].' '.__('deals'), 'tone' => 'info', 'icon' => 'o-building-office-2'],
                ['label' => __('In Progress'), 'value' => $s['draftCount'], 'sub' => __('awaiting finalization'), 'tone' => 'warning', 'icon' => 'o-document-text'],
                ['label' => __('Materials'), 'value' => $s['materialReceived'].' / '.$s['materialTotal'], 'sub' => __('received of all required'), 'tone' => 'primary', 'icon' => 'o-cube'],
            ];
        @endphp

        <div class="space-y-6">
            {{-- Hero --}}
            <div class="bg-gradient-brand rounded-box p-6 text-white shadow-soft-lg">
                <p class="eyebrow text-white/70">{{ __('Sponsorship Report') }}</p>
                <h1 class="text-2xl font-extrabold tracking-tight">{{ __('APCN 2027') }}</h1>
                <p class="mt-1 text-sm text-white/70">{{ __('Generated') }} {{ now()->format('d M Y · H:i') }}</p>
            </div>

            {{-- Summary cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cards as $card)
                    <div class="card bg-base-100 p-4">
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

            {{-- Sponsors that have dealt --}}
            <x-card>
                <h2 class="text-lg font-extrabold">{{ __('Sponsors') }}</h2>
                <p class="mt-1 text-sm text-base-content/60">{{ __('Companies that have deals. Tap a sponsor to see the detail.') }}</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="table min-w-[36rem]">
                        <thead>
                            <tr>
                                <th>{{ __('Sponsor') }}</th>
                                <th>{{ __('Level') }}</th>
                                <th class="text-right">{{ __('Deals') }}</th>
                                <th class="text-right">{{ __('Total Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['sponsors'] as $sponsor)
                                <tr wire:key="sponsor-{{ $sponsor->id }}">
                                    <td class="font-semibold">
                                        <a href="{{ route('public.report.sponsor', ['token' => $token, 'sponsor' => $sponsor]) }}" class="link link-primary" wire:navigate>{{ $sponsor->company_name }}</a>
                                    </td>
                                    <td><x-tier-badge :package="$sponsor->topPackage()" /></td>
                                    <td class="text-right">{{ $sponsor->deals_count }}</td>
                                    <td class="whitespace-nowrap text-right">Rp {{ number_format((float) $sponsor->deals_sum_final_price, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-base-content/50">{{ __('No sponsors yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            {{-- Committed (finalized) sponsors --}}
            <x-card>
                <h2 class="text-lg font-extrabold">{{ __('Committed Sponsors') }}</h2>
                <p class="mt-1 text-sm text-base-content/60">{{ __('Finalized deals.') }}</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="table min-w-[40rem]">
                        <thead>
                            <tr>
                                <th>{{ __('Deal #') }}</th>
                                <th>{{ __('Sponsor') }}</th>
                                <th>{{ __('Package') }}</th>
                                <th class="whitespace-nowrap text-right">{{ __('Committed') }}</th>
                                <th class="whitespace-nowrap text-right">{{ __('Paid') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['finalizedDeals'] as $deal)
                                <tr wire:key="fin-{{ $deal->id }}">
                                    <td class="font-semibold">{{ $deal->deal_number }}</td>
                                    <td>
                                        <a href="{{ route('public.report.sponsor', ['token' => $token, 'sponsor' => $deal->sponsor_id]) }}" class="link link-primary" wire:navigate>{{ $deal->sponsor->company_name }}</a>
                                    </td>
                                    <td><x-tier-badge :package="$deal->package" /></td>
                                    <td class="whitespace-nowrap text-right">Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap text-right">Rp {{ number_format((float) ($deal->paid_total ?? 0), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-base-content/50">{{ __('No finalized deals yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            {{-- In progress (draft) sponsors --}}
            <x-card>
                <h2 class="text-lg font-extrabold">{{ __('In Progress') }}</h2>
                <p class="mt-1 text-sm text-base-content/60">{{ __('Deals still being negotiated (awaiting finalization).') }}</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="table min-w-[34rem]">
                        <thead>
                            <tr>
                                <th>{{ __('Deal #') }}</th>
                                <th>{{ __('Sponsor') }}</th>
                                <th>{{ __('Package') }}</th>
                                <th class="whitespace-nowrap text-right">{{ __('Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['draftDeals'] as $deal)
                                <tr wire:key="draft-{{ $deal->id }}">
                                    <td class="font-semibold">{{ $deal->deal_number }}</td>
                                    <td>
                                        <a href="{{ route('public.report.sponsor', ['token' => $token, 'sponsor' => $deal->sponsor_id]) }}" class="link link-primary" wire:navigate>{{ $deal->sponsor->company_name }}</a>
                                    </td>
                                    <td><x-tier-badge :package="$deal->package" /></td>
                                    <td class="whitespace-nowrap text-right">Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-base-content/50">{{ __('No deals in progress.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            {{-- Tier & item uptake --}}
            <div class="grid gap-6 lg:grid-cols-2">
                <x-card>
                    <h2 class="text-lg font-extrabold">{{ __('Tier Uptake') }}</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Package') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th>{{ __('Taken') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['packageUptake'] as $package)
                                    <tr wire:key="pkg-{{ $package->id }}">
                                        <td class="font-semibold">{{ $package->name }}</td>
                                        <td>Rp {{ number_format((float) $package->default_price, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge badge-soft whitespace-nowrap {{ $package->quota !== null && $package->taken_count >= $package->quota ? 'badge-error' : 'badge-ghost' }}">
                                                {{ $package->taken_count }} / {{ $package->quota ?? '∞' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card>
                    <h2 class="text-lg font-extrabold">{{ __('Limited Items') }}</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Item') }}</th>
                                    <th>{{ __('Taken') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['itemUptake'] as $item)
                                    <tr wire:key="item-{{ $item->id }}">
                                        <td class="font-semibold">{{ $item->name }}</td>
                                        <td>
                                            <span class="badge badge-soft whitespace-nowrap {{ $item->taken_count >= $item->quota ? 'badge-error' : 'badge-ghost' }}">
                                                {{ $item->taken_count }} / {{ $item->quota }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-base-content/50">{{ __('No limited items.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    @endif
</section>
