<?php

declare(strict_types=1);

namespace App\Enums;

enum InterestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return __('enums.interest_status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Accepted => 'brand',
            self::Pending => 'gold',
            self::Declined => 'red',
            self::Cancelled, self::Expired => 'stone',
        };
    }

    /** A request that can still change state. */
    public function isOpen(): bool
    {
        return $this === self::Pending;
    }
}
