<?php

declare(strict_types=1);

namespace App\Enums;

enum BiodataType: string
{
    case Groom = 'groom';
    case Bride = 'bride';

    public function label(): string
    {
        return __('enums.biodata_type.'.$this->value);
    }

    /** The type a member of this type looks for. */
    public function opposite(): self
    {
        return match ($this) {
            self::Groom => self::Bride,
            self::Bride => self::Groom,
        };
    }

    public function badge(): string
    {
        return 'brand';
    }
}
