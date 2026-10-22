<?php

declare(strict_types=1);

namespace App\Enums;

/** Status of one identity verification request. */
enum IdentityStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('enums.identity_status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Approved => 'brand',
            self::Pending => 'gold',
            self::Rejected => 'red',
        };
    }

    /** A decided request never goes back to pending. */
    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
