<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why a request was rejected. A fixed list on purpose: no free text, so no personal
 * detail can end up in a notification, a log or the database.
 */
enum IdentityRejectionReason: string
{
    case Unreadable = 'unreadable';
    case Mismatch = 'mismatch';
    case Expired = 'expired';
    case NotAccepted = 'not_accepted';
    case Other = 'other';

    public function label(): string
    {
        return __('enums.identity_rejection_reason.'.$this->value);
    }
}
