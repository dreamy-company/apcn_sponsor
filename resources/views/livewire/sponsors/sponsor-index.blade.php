<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ __('Sponsors') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('Companies sponsoring APCN 2027 and the deals they took.') }}</p>
            </div>
        </div>

        <x-card>
            <x-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                :placeholder="__('Search sponsors...')"
                class="max-w-sm"
            />

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Level') }}</th>
                            <th>{{ __('PIC') }}</th>
                            <th>{{ __('Deals') }}</th>
                            <th>{{ __('Total Value') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sponsors as $sponsor)
                            <tr wire:key="{{ $sponsor->id }}">
                                <td class="font-semibold">
                                    <a href="{{ route('sponsors.show', $sponsor) }}" class="link link-primary" wire:navigate>{{ $sponsor->company_name }}</a>
                                </td>
                                <td><x-tier-badge :package="$sponsor->topPackage()" /></td>
                                <td>
                                    <div>{{ $sponsor->pic_name }}</div>
                                    <div class="text-xs text-base-content/50">{{ $sponsor->pic_contact }}</div>
                                </td>
                                <td>{{ $sponsor->deals_count }}</td>
                                <td>Rp {{ number_format((float) $sponsor->deals_sum_final_price, 0, ',', '.') }}</td>
                                <td>
                                    <div class="flex gap-1">
                                        <x-button icon="o-eye" :link="route('sponsors.show', $sponsor)" class="btn-ghost btn-sm btn-square" />
                                        @if ($sponsor->deals_count === 0)
                                            <x-button
                                                icon="o-trash"
                                                wire:click="delete({{ $sponsor->id }})"
                                                wire:confirm="{{ __('Delete this sponsor?') }}"
                                                class="btn-ghost btn-sm btn-square text-error"
                                            />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-base-content/50">{{ __('No sponsors yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</section>
