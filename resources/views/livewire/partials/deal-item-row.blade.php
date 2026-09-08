{{-- One row of the item picker.
     $index — key into the $items array (never re-sorted; wire:model binds to it) --}}
@php
    $item = $items[$index];
    $itemQuota = $item['quota'] ?? null;
    $taken = $itemsTaken[$item['item_id']] ?? 0;
    $remaining = $itemQuota !== null ? max(0, $itemQuota - $taken) : null;
    $units = max(1, (int) $item['quantity']);
    $full = $itemQuota !== null && $remaining < $units;
    $blocked = $full && ! $item['checked'];
@endphp

<div wire:key="item-{{ $item['item_id'] }}" @class([
    'rounded-box border transition',
    'border-primary bg-primary-soft' => $item['checked'],
    'border-base-300 hover:border-primary/40' => ! $item['checked'] && ! $blocked,
    'border-base-300 opacity-60' => $blocked,
])>
    <label @class([
        'flex items-start justify-between gap-3 p-3',
        'cursor-pointer' => ! $blocked,
        'cursor-not-allowed' => $blocked,
    ])>
        <div class="flex min-w-0 items-start gap-3">
            <x-checkbox wire:model.live="items.{{ $index }}.checked" @disabled($blocked) />
            <div class="min-w-0">
                <div class="text-sm font-semibold">{{ $item['name'] }}</div>
                <div class="flex flex-wrap items-center gap-1.5 text-xs text-base-content/50">
                    <span>{{ $item['is_addon'] ? __('Add-on') : __('Package item') }}</span>
                    @if ($itemQuota !== null)
                        <span class="badge badge-soft badge-xs {{ $full ? 'badge-error' : 'badge-ghost' }}">
                            {{ $taken }}/{{ $itemQuota }}
                            {{ $full ? __('Full') : __(':n left', ['n' => $remaining]) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <x-input
                wire:model.live.debounce.400ms="items.{{ $index }}.quantity"
                class="w-16"
                type="number"
                min="1"
                step="1"
                :title="__('Units')"
                @click.stop
            />
            @if ($item['is_addon'])
                <div class="w-40" @click.stop>
                    <x-money-input
                        wire:model="items.{{ $index }}.custom_price"
                        :currency="$currencyEnum"
                        :placeholder="__('Price')"
                    />
                </div>
            @endif
        </div>
    </label>
