<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Bangladeshi mobile numbers for SMS. The local form 01XXXXXXXXX is what we store;
 * the provider wants 8801XXXXXXXXX.
 */
final class SmsNumber
{
    /**
     * Local form (01XXXXXXXXX) or null when the input is not a valid mobile number.
     * Accepts Bangla digits, spaces, dashes and +880 / 880 in front.
     */
    public static function normalize(?string $value): ?string
    {
        $text = preg_replace('/[\s\-]+/u', '', (string) Digits::normalize($value)) ?? '';
        $text = preg_replace('/^(\+?880)/', '0', $text) ?? '';

        return preg_match('/^01[3-9][0-9]{8}$/', $text) === 1 ? $text : null;
    }

    /** 01712345678 becomes 8801712345678. The input must already be in the local form. */
    public static function international(string $local): string
    {
        return '880'.substr($local, 1);
    }

    /** 01712345678 becomes 017****678. Safe to show on screen. */
    public static function mask(string $local): string
    {
        return substr($local, 0, 3).'****'.substr($local, -3);
    }

    /** Keyed hash for the unique check. The key is the application key. */
    public static function hash(string $local): string
    {
        return hash_hmac('sha256', 'phone|'.$local, (string) config('app.key'));
    }
}
