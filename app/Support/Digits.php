<?php

declare(strict_types=1);

namespace App\Support;

final class Digits
{
    private const ASCII = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const BANGLA = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    /**
     * Format digits for display. Bangla locale shows Bangla digits.
     */
    public static function localize(int|float|string|null $value, ?string $locale = null): string
    {
        $text = (string) $value;

        if (($locale ?? app()->getLocale()) !== 'bn') {
            return $text;
        }

        return str_replace(self::ASCII, self::BANGLA, $text);
    }

    /**
     * Convert Bangla digits typed by a user into ASCII digits.
     * Use before validating or storing phone numbers, years, amounts, etc.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace(self::BANGLA, self::ASCII, $value);
    }
}
