<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Read-only helper around config/countries.php.
 */
final class Countries
{
    public const HOME = 'BD';

    /** @return array<string, string> code => name in the current locale */
    public static function options(?string $locale = null): array
    {
        $options = [];

        foreach (array_keys(self::all()) as $code) {
            $options[$code] = self::name($code, $locale);
        }

        return $options;
    }

    public static function name(?string $code, ?string $locale = null): string
    {
        if ($code === null || ! self::exists($code)) {
            return '';
        }

        $locale ??= app()->getLocale();
        $names = self::all()[$code];

        return $names[$locale] ?? $names['en'];
    }

    public static function exists(string $code): bool
    {
        return array_key_exists($code, self::all());
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function isAbroad(?string $code): bool
    {
        return $code !== null && $code !== '' && $code !== self::HOME;
    }

    /** @return array<string, array{en: string, bn: string}> */
    private static function all(): array
    {
        return config('countries', []);
    }
}
