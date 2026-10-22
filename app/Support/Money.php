<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shows an amount that is stored as whole poisha (1 taka = 100 poisha). Only integer
 * arithmetic is used, so no rounding error can appear.
 */
final class Money
{
    public static function format(int $minor, string $currency = 'BDT'): string
    {
        $negative = $minor < 0;
        $minor = abs($minor);

        $taka = intdiv($minor, 100);
        $poisha = $minor % 100;

        $text = number_format($taka);

        if ($poisha !== 0) {
            $text .= '.'.str_pad((string) $poisha, 2, '0', STR_PAD_LEFT);
        }

        $symbol = $currency === 'BDT' ? '৳' : $currency;

        return ($negative ? '-' : '').$symbol.' '.Digits::localize($text);
    }
}
