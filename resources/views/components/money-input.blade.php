@props([
    // App\Enums\Currency or its string value; drives grouping and decimals.
    'currency' => 'IDR',
    'label' => null,
    'hint' => null,
    'placeholder' => '0',
])
@php
    $resolved = $currency instanceof \App\Enums\Currency
        ? $currency
        : (\App\Enums\Currency::tryFrom((string) $currency) ?? \App\Enums\Currency::IDR);

    // The Livewire property this field writes to, so Alpine can mirror it.
    $model = $attributes->wire('model')->value();
@endphp

{{-- Re-keyed on currency so the mask re-initialises when IDR/USD is switched. --}}
<div x-data="moneyInput({ currency: '{{ $resolved->value }}', model: '{{ $model }}' })"
     wire:key="money-{{ $model }}-{{ $resolved->value }}"
     wire:ignore.self>
    <x-input
        :label="$label"
        :hint="$hint"
        :prefix="$resolved->symbol()"
        :placeholder="$placeholder"
        type="text"
        inputmode="decimal"
        x-model="display"
        x-on:input="onInput($event)"
        x-on:blur="onBlur()"
        :error-field="$model"
        {{ $attributes->whereDoesntStartWith('wire:model') }}
    />
</div>
