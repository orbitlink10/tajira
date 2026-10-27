<?php

namespace App\Support;

class Money
{
    public static function format(?int $minor, string $currency = 'KES'): string
    {
        if ($minor === null) {
            return 'Request price';
        }

        $amount = $minor / 100;
        $decimals = $minor % 100 === 0 ? 0 : 2;

        return $currency.' '.number_format($amount, $decimals, '.', ',');
    }

    public static function parseToMinor(string|int|float|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $normalised = preg_replace('/[^0-9.\-]/', '', (string) $value);

        if ($normalised === '' || ! is_numeric($normalised)) {
            return null;
        }

        return (int) round(((float) $normalised) * 100);
    }

    public static function minorToDecimal(?int $minor): ?float
    {
        return $minor === null ? null : round($minor / 100, 2);
    }

    public static function percentOf(int $amountMinor, int $rateBasisPoints): int
    {
        return (int) round($amountMinor * $rateBasisPoints / 10000);
    }
}
