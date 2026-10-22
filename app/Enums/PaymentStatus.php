<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status of one payment. UnderReview is used by the manual bKash flow: the member
 * has sent the money and submitted the TrxID, and staff still has to check it.
 * Subscription status is a separate enum on purpose.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Paid => 'brand',
            self::Pending, self::UnderReview => 'gold',
            self::Failed => 'red',
            self::Cancelled, self::Refunded => 'stone',
        };
    }

    /** A final payment never goes back to pending or under review. */
    public function isFinal(): bool
    {
        return $this !== self::Pending && $this !== self::UnderReview;
    }
}
