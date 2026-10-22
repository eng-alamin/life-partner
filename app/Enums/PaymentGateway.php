<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * How a payment is made. The manual bKash flow comes first; the bKash API and
 * SSLCommerz are added later as separate drivers.
 */
enum PaymentGateway: string
{
    use HasLabel;

    case ManualBkash = 'manual_bkash';
    case Bkash = 'bkash';
    case Sslcommerz = 'sslcommerz';

    /**
     * Names the place a gateway reference (for example a bKash TrxID) is unique in.
     * Manual bKash and the bKash API share one scope, so the same TrxID can never
     * be counted twice, even if it is submitted by hand and also arrives by API.
     */
    public function refScope(): string
    {
        return match ($this) {
            self::ManualBkash, self::Bkash => 'bkash',
            self::Sslcommerz => 'sslcommerz',
        };
    }

    /** True when staff, not a gateway, confirms the payment. */
    public function isManual(): bool
    {
        return $this === self::ManualBkash;
    }
}
