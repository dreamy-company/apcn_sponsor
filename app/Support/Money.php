<?php

namespace App\Support;

class Money
{
    /**
     * The plain numeric string to seed a form field with.
     *
     * Money is stored as DECIMAL(15,2) and cast to `decimal:2`, so a whole
     * amount arrives as "452500000.00". Showing that in an input is the source
     * of the stray ",00" the team complained about, so trailing zeros — and a
     * trailing dot — are trimmed. Null/empty stays empty, which the forms treat
     * as "no price given".
     */
    public static function plain(int|float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $value = (string) $amount;

        if (! str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.');
    }
}
