<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __('admin_users.status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'brand',
            self::Suspended => 'red',
        };
    }
}
