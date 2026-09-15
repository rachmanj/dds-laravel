<?php

namespace App\Support;

class CompactNumberFormatter
{
    public static function format(float|int|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '-';
        }

        $numericValue = (float) $value;
        $abs = abs($numericValue);

        if ($abs >= 1_000_000_000) {
            return self::formatScaled($numericValue, 1_000_000_000, 'miliar', $decimals);
        }

        if ($abs >= 1_000_000) {
            return self::formatScaled($numericValue, 1_000_000, 'juta', $decimals);
        }

        if ($abs >= 1_000) {
            return self::formatScaled($numericValue, 1_000, 'ribu', $decimals);
        }

        return number_format($numericValue, 0, ',', '.');
    }

    private static function formatScaled(
        float $value,
        float $divisor,
        string $suffix,
        int $decimals
    ): string {
        $scaled = $value / $divisor;

        return number_format($scaled, $decimals, ',', '.').' '.$suffix;
    }
}
