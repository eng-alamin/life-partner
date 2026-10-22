<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * A way of handing one SMS to a provider. The rest of the application only knows
 * this interface, so a provider can be replaced by writing one new class and
 * changing LP_SMS_DRIVER.
 */
interface SmsGateway
{
    /** False when this gateway cannot really send (not configured, or switched off). */
    public function isReady(): bool;

    /**
     * @param  string  $localNumber  01XXXXXXXXX
     */
    public function send(string $localNumber, string $message): SmsResult;
}
