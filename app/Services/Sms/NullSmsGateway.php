<?php

declare(strict_types=1);

namespace App\Services\Sms;

/** Sends nothing. Used when SMS is switched off (LP_SMS_DRIVER=null). */
final class NullSmsGateway implements SmsGateway
{
    public function isReady(): bool
    {
        return false;
    }

    public function send(string $localNumber, string $message): SmsResult
    {
        return SmsResult::failed(SmsResult::NOT_CONFIGURED, false);
    }
}
