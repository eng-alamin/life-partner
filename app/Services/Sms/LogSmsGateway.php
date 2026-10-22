<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\SmsNumber;
use Illuminate\Support\Facades\Log;

/**
 * For local work and tests: writes the SMS to the log instead of sending it.
 * It refuses to run in production (isReady is false there) so nobody believes a
 * message was sent when it was only logged. The message text is logged only
 * outside production, because it can hold a verification code.
 */
final class LogSmsGateway implements SmsGateway
{
    public function isReady(): bool
    {
        return ! app()->isProduction();
    }

    public function send(string $localNumber, string $message): SmsResult
    {
        if (! $this->isReady()) {
            return SmsResult::failed(SmsResult::NOT_CONFIGURED, false);
        }

        Log::info('sms.log_driver', [
            'to' => SmsNumber::mask($localNumber),
            'length' => mb_strlen($message),
            'text' => $message,
        ]);

        return SmsResult::sent('log');
    }
}
