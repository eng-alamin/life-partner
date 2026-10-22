<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Enums\SettingKey;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Reads the site settings. All rows are cached together for ten minutes and the cache is
 * cleared whenever staff save. A setting that was never saved gives its default.
 *
 * If the settings table does not exist yet (code deployed before "php artisan migrate"),
 * the defaults are used instead of breaking every page.
 */
final class SiteSettings
{
    public const CACHE_KEY = 'settings.all';

    /** @return array<string, ?string> One entry for every SettingKey. */
    public function all(): array
    {
        try {
            /** @var array<string, ?string> $stored */
            $stored = Cache::remember(
                self::CACHE_KEY,
                600,
                fn (): array => Setting::query()->pluck('value', 'key')->all(),
            );
        } catch (QueryException) {
            $stored = [];
        }

        $values = [];

        foreach (SettingKey::cases() as $key) {
            $values[$key->value] = array_key_exists($key->value, $stored) ? $stored[$key->value] : $key->default();
        }

        return $values;
    }

    /** The trimmed value, or null when it is empty or not set. */
    public function get(SettingKey $key): ?string
    {
        $value = $this->all()[$key->value] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** Anything except an explicit "0" counts as open. */
    public function registrationOpen(): bool
    {
        return $this->get(SettingKey::RegistrationOpen) !== '0';
    }

    public function contactEmail(): ?string
    {
        return $this->get(SettingKey::ContactEmail);
    }

    public function contactPhone(): ?string
    {
        return $this->get(SettingKey::ContactPhone);
    }

    /** The address in the reader's language; the other language when that one is empty. */
    public function contactAddress(): ?string
    {
        [$first, $second] = app()->getLocale() === 'en'
            ? [SettingKey::ContactAddressEn, SettingKey::ContactAddressBn]
            : [SettingKey::ContactAddressBn, SettingKey::ContactAddressEn];

        return $this->get($first) ?? $this->get($second);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
