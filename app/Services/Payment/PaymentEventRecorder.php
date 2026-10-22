<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentEvent;

/**
 * Adds one audit row for a payment. The payload must never hold a phone number,
 * a TrxID, a file name or a secret: only short facts such as "by staff".
 */
final class PaymentEventRecorder
{
    /** @param  array<string, bool|int|string|null>  $payload */
    public function record(Payment $payment, string $type, array $payload = []): PaymentEvent
    {
        $event = new PaymentEvent;
        $event->forceFill([
            'payment_id' => $payment->getKey(),
            'gateway' => $payment->gateway->value,
            'event_type' => $type,
            'payload' => $payload,
            'processed_at' => now(),
        ])->save();

        return $event;
    }
}
