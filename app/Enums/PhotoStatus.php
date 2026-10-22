<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** Review state of one photo. Only approved photos can be shown to other members. */
enum PhotoStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Badge variant understood by <x-badge>. */
    public function badge(): string
    {
        return match ($this) {
            self::Approved => 'brand',
            self::Pending => 'gold',
            self::Rejected => 'red',
        };
    }
}
