<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Handled = 'handled';
    case Spam = 'spam';

    public function label(): string
    {
        return __('contact.status.'.$this->value);
    }

    /** Badge variant understood by <x-badge>. */
    public function badge(): string
    {
        return match ($this) {
            self::New => 'gold',
            self::Handled => 'brand',
            self::Spam => 'stone',
        };
    }
}
