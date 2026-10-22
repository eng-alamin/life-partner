<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kept separate from PaymentStatus on purpose: a payment can be refunded
 * while the subscription history still needs to be preserved.
 */
enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('enums.subscription_status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'brand',
            self::Pending => 'gold',
            self::Expired, self::Cancelled => 'stone',
        };
    }
}
