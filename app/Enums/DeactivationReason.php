<?php

declare(strict_types=1);

namespace App\Enums;

/** Why a member paused their biodata. Optional and chosen from this fixed list only. */
enum DeactivationReason: string
{
    case FoundHere = 'found_here';
    case FoundElsewhere = 'found_elsewhere';
    case Break = 'break';
    case Other = 'other';

    public function label(): string
    {
        return __('visibility.deactivate.reasons.'.$this->value);
    }
}
