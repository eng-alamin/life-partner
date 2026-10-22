<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * Gives a string-backed enum a translated label and select-box options.
 * Labels live in lang/{locale}/enums.php under the snake_case enum name,
 * for example App\Enums\MaritalStatus => enums.marital_status.never_married.
 */
trait HasLabel
{
    public function label(): string
    {
        return __('enums.'.self::langKey().'.'.$this->value);
    }

    public static function langKey(): string
    {
        return Str::snake(class_basename(static::class));
    }

    /** @return array<string, string> value => translated label, in case order */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
