<?php

namespace App\Enums;

enum Currency: string
{
    case IDR = 'IDR';
    case USD = 'USD';

    public function label(): string
    {
        return match ($this) {
            self::IDR => 'Rupiah (IDR)',
            self::USD => 'US Dollar (USD)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::IDR => 'Rp',
            self::USD => '$',
        };
    }

    /**
     * Catalog column holding the rate-card price in this currency.
     */
    public function priceColumn(): string
    {
        return match ($this) {
            self::IDR => 'default_price_idr',
            self::USD => 'default_price_usd',
        };
    }

    /**
     * Format an amount using this currency's conventions:
     * IDR uses dot thousands and no decimals, USD uses comma thousands and cents.
     */
    public function format(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);

        return match ($this) {
            self::IDR => 'Rp '.number_format($value, 0, ',', '.'),
            self::USD => '$'.number_format($value, 2, '.', ','),
        };
    }
}
