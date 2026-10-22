<?php

declare(strict_types=1);

namespace App\Enums;

enum PhotoVisibility: string
{
    case Hidden = 'hidden';
    case AfterAcceptance = 'after_acceptance';
    case ApprovedMembers = 'approved_members';

    public function label(): string
    {
        return __('enums.photo_visibility.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Hidden => 'stone',
            self::AfterAcceptance => 'gold',
            self::ApprovedMembers => 'brand',
        };
    }
}
