<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Peso amounts as integer centavos, so thresholds and costs compare exactly
 * instead of through floats (e.g. 5000.00 vs 5000.01).
 */
final class Money
{
    /**
     * Convert an amount to centavos, rounding half up to two decimals.
     */
    public static function centavos(string|float|int $amount): int
    {
        if (is_int($amount)) {
            return $amount * 100;
        }

        $value = is_float($amount) ? number_format($amount, 3, '.', '') : trim($amount);

        if (preg_match('/^(-?)(\d*)(?:\.(\d*))?$/', $value, $matches) !== 1 || ($matches[2] === '' && ($matches[3] ?? '') === '')) {
            throw new InvalidArgumentException("Not a valid amount: [{$value}].");
        }

        $fraction = str_pad($matches[3] ?? '', 3, '0');
        $centavos = (int) $matches[2] * 100 + (int) substr($fraction, 0, 2);

        if ((int) $fraction[2] >= 5) {
            $centavos++;
        }

        return $matches[1] === '-' ? -$centavos : $centavos;
    }

    /**
     * Format centavos as a plain two-decimal amount, e.g. "50000.00".
     */
    public static function fromCentavos(int $centavos): string
    {
        $sign = $centavos < 0 ? '-' : '';
        $centavos = abs($centavos);

        return sprintf('%s%d.%02d', $sign, intdiv($centavos, 100), $centavos % 100);
    }
}
