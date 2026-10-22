<?php

declare(strict_types=1);

namespace App\Actions\Phone;

use App\Enums\OtpPurpose;
use App\Enums\OtpResult;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Otp\SmsOtpChannel;
use App\Support\SmsNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Checks the code and, if it is right, marks the number as verified.
 *
 * The unique hash is written only here. If another member verified the same number
 * a moment earlier, the database refuses the second one and this member's number is
 * cleared (their code was right, but the number is no longer free).
 */
final class ConfirmPhoneVerification
{
    public function __construct(private readonly SmsOtpChannel $channel) {}

    /**
     * @throws ValidationException when the number was taken in the meantime
     */
    public function handle(User $user, string $code): OtpResult
    {
        $phone = (string) $user->phone;

        if ($phone === '' || $user->phone_verified_at !== null) {
            return OtpResult::Expired;
        }

        $result = (new OtpService($this->channel))->verify($user, OtpPurpose::Phone, $code);

        if ($result !== OtpResult::Verified) {
            return $result;
        }

        try {
            $user->forceFill([
                'phone_hash' => SmsNumber::hash($phone),
                'phone_verified_at' => now(),
            ])->save();
        } catch (UniqueConstraintViolationException) {
            $user->forceFill(['phone' => null, 'phone_hash' => null, 'phone_verified_at' => null])->save();

            throw ValidationException::withMessages(['phone' => __('sms.errors.unavailable')]);
        }

        // No number in the log, only the fact.
        activity('account')->causedBy($user)->log('phone.verified');

        return OtpResult::Verified;
    }
}
