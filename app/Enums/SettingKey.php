<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The only settings staff can change from the admin panel. The list is fixed in code, so no
 * screen can store a name of its own. Money, payment numbers, SMS and privacy rules are not here
 * on purpose: they stay in .env / config.
 */
enum SettingKey: string
{
    case RegistrationOpen = 'registration_open';
    case ContactEmail = 'contact_email';
    case ContactPhone = 'contact_phone';
    case ContactAddressBn = 'contact_address_bn';
    case ContactAddressEn = 'contact_address_en';

    /** The value used until staff save something. Null means "not set". */
    public function default(): ?string
    {
        return match ($this) {
            self::RegistrationOpen => '1',
            default => null,
        };
    }
}
