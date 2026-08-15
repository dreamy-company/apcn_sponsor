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
                    <p class="mt-1 text-base-content/60">{{ __('Enter the access code to view :name\'s sponsorship detail.', ['name' => $sponsor->company_name]) }}</p>
                </div>

                <form wire:submit="unlock" class="mt-5 space-y-4">
                    <x-input wire:model="code" :label="__('Access code')" icon="o-key" autofocus />
                    <x-button :label="__('View detail')" type="submit" class="btn-primary w-full" spinner="unlock" />
                </form>
            </x-card>
        </div>
    @else
        <div class="space-y-6">
            {{-- Header --}}
            <div class="bg-gradient-brand rounded-box p-6 text-white shadow-soft-lg">
                <a href="{{ route('public.report', $token) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-white/70 hover:text-white">
                    <x-icon name="o-arrow-left" class="size-4" /> {{ __('Back to report') }}
                </a>
                <p class="eyebrow mt-3 text-white/70">{{ __('Sponsor') }}</p>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold tracking-tight">{{ $sponsor->company_name }}</h1>
                    <x-tier-badge :package="$topPackage" size="lg" />
                </div>
                <p class="mt-1 text-sm text-white/70">
                    {{ $deals->count() }} {{ __('deal(s)') }} · Rp {{ number_format((float) $totalValue, 0, ',', '.') }}
                </p>
            </div>

            {{-- Deals --}}
            @forelse ($deals as $deal)
                <details class="collapse-arrow card collapse bg-base-100" @if ($loop->first) open @endif>
                    <summary class="collapse-title">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-extrabold">{{ $deal->deal_number }}</span>
                                    <x-tier-badge :package="$deal->package" />
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold whitespace-nowrap">Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</span>
                                <span class="badge badge-soft {{ $deal->status === \App\Enums\DealStatus::Finalized ? 'badge-success' : 'badge-ghost' }}">
                                    {{ $deal->status->label() }}
                                </span>
                            </div>
                        </div>
                    </summary>
                    <div class="collapse-content">
                        <x-public-deal-detail :deal="$deal" />
                    </div>
                </details>
            @empty
                <x-card>
                    <p class="text-center text-base-content/50">{{ __('No deals for this sponsor yet.') }}</p>
                </x-card>
            @endforelse
        </div>
    @endif
</section>
