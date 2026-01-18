<?php

namespace App\Helpers;

class MoneyHelper
{
    /**
     * Convert cents to a formatted money string.
     */
    public static function toMoney(int|string|null $cents): string
    {
        return number_format(((int) ($cents ?? 0)) / 100, 2);
    }

    /**
     * Convert a money amount to cents.
     */
    public static function toCents(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
