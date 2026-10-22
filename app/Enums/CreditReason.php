<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** Why a row was added to the credit ledger. */
enum CreditReason: string
{
    use HasLabel;

    case Purchase = 'purchase';
    case MembershipGrant = 'membership_grant';
    case FreeGrant = 'free_grant';
    case AdminAdjust = 'admin_adjust';
    case Unlock = 'unlock';
    case RefundReversal = 'refund_reversal';

    /** Reasons that add credits. */
    public function isGrant(): bool
    {
        return match ($this) {
            self::Purchase, self::MembershipGrant, self::FreeGrant, self::AdminAdjust => true,
            self::Unlock, self::RefundReversal => false,
        };
    }

    /** Grants that belong to one payment and may happen only once per payment. */
    public function isPaymentBacked(): bool
    {
        return $this === self::Purchase || $this === self::MembershipGrant;
    }
}
