<?php

declare(strict_types=1);

namespace App\Enums;

enum BiodataStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Hidden = 'hidden';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return __('enums.biodata_status.'.$this->value);
    }

    /** Badge variant understood by <x-badge>. */
    public function badge(): string
    {
        return match ($this) {
            self::Approved => 'brand',
            self::Pending => 'gold',
            self::Rejected => 'red',
            self::Draft, self::Hidden, self::Deactivated => 'stone',
        };
    }

    /** Only approved biodatas may appear in search results. */
    public function isSearchable(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Statuses in which the owner may edit the biodata. A pending biodata is
     * editable: it stays pending and the reviewer is told if it changed.
     */
    public function isMemberEditable(): bool
    {
        return in_array($this, [self::Draft, self::Pending, self::Rejected, self::Approved, self::Hidden], true);
    }
}
