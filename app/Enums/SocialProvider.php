<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Facebook => 'Facebook',
        };
    }

    /** A provider is offered only when its keys are present in .env. */
    public function isConfigured(): bool
    {
        return filled(config('services.'.$this->value.'.client_id'))
            && filled(config('services.'.$this->value.'.client_secret'));
    }
}
