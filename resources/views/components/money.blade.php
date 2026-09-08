@props([
    // Amount to render. Null/'' renders the placeholder.
    'amount' => null,
    // App\Enums\Currency, its string value ('IDR'/'USD'), or null for IDR.
    'currency' => null,
    // Shown when $amount is null or an empty string.
    'placeholder' => '—',
])
@php
    $resolved = $currency instanceof \App\Enums\Currency
        ? $currency
        : (\App\Enums\Currency::tryFrom((string) $currency) ?? \App\Enums\Currency::IDR);
@endphp
@if ($amount === null || $amount === '')
    <span {{ $attributes }}>{{ $placeholder }}</span>
@else
    <span {{ $attributes }}>{{ $resolved->format($amount) }}</span>
@endif
