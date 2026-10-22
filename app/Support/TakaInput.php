<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Turns the taka amount a person types (for example "499.50" or "৪৯৯.৫০") into whole
 * poisha, and back. Only string and integer arithmetic is used, so no float rounding
 * error can appear. At most 2 decimal places, at most 7 digits before the dot.
 */
final class TakaInput
{
    /** Largest amount accepted: 9,999,999.99 taka. */
    public const MAX_MINOR = 999_999_999;

    /** Whole poisha, or null when the text is not a valid amount. */
    public static function toMinor(?string $value): ?int
    {
        $text = preg_replace('/\s+/u', '', (string) Digits::normalize($value)) ?? '';

        if (preg_match('/^(\d{1,7})(?:\.(\d{1,2}))?$/', $text, $match) !== 1) {
            return null;
        }

        $poisha = isset($match[2]) ? (int) str_pad($match[2], 2, '0') : 0;

        return ((int) $match[1]) * 100 + $poisha;
    }

    /** Plain text for an input box: "500" or "499.50" (no symbol, no grouping). */
    public static function toField(int $minor): string
    {
        $minor = max(0, $minor);
        $taka = intdiv($minor, 100);
        $poisha = $minor % 100;

        return $poisha === 0 ? (string) $taka : $taka.'.'.str_pad((string) $poisha, 2, '0', STR_PAD_LEFT);
    }
}
