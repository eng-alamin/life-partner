<?php

declare(strict_types=1);

namespace App\Services\Otp;

use App\Enums\OtpPurpose;
use App\Exceptions\SmsDeliveryException;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsResult;

/**
 * Delivers a one-time code by SMS to the number the member is verifying.
 *
 * It sends right away inside the request instead of queueing: a queued job would
 * keep the code in the jobs table. The code is never logged.
 */
class SmsOtpChannel implements OtpChannel
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function name(): string
    {
        return 'sms';
    }

    public function send(User $user, string $code, OtpPurpose $purpose): void
    {
        $phone = (string) $user->phone;

        if ($phone === '' || ! $this->gateway->isReady()) {
            throw new SmsDeliveryException(SmsResult::NOT_CONFIGURED);
        }

        $text = __('sms.otp', [
            'app' => config('app.name'),
            'code' => $code,
            'minutes' => OtpService::TTL_MINUTES,
        ], $user->preferredLocale());

        $result = $this->gateway->send($phone, $text);

        if (! $result->ok) {
            throw new SmsDeliveryException($result->errorCode);
        }
    }
}
