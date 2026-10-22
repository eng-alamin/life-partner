<?php

declare(strict_types=1);

namespace App\Enums;

enum GuardianApprovalStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';

    public function label(): string
    {
        return __('enums.guardian_approval_status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Approved => 'brand',
            self::Pending => 'gold',
            self::Declined => 'red',
            self::NotRequired => 'stone',
        };
    }
}
