<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Dismissed = 'dismissed';
    case Resolved = 'resolved';

    public function label(): string
    {
        return __('reports.status.'.$this->value);
    }

    /** Badge variant understood by <x-badge>. */
    public function badge(): string
    {
        return match ($this) {
            self::Open => 'gold',
            self::Resolved => 'brand',
            self::Dismissed => 'stone',
        };
    }
}
