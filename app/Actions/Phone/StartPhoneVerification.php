<?php

declare(strict_types=1);

namespace App\Actions\Phone;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpThrottledException;
use App\Exceptions\SmsDeliveryException;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Otp\SmsOtpChannel;
use App\Support\SmsNumber;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Saves a number the member typed (not yet trusted) and sends a code to it.
 *
 * Abuse guards, because every code costs money and can be aimed at a stranger:
 * - the member must already have a verified email (the route group makes sure);
 * - one member may start at most 5 verifications a day;
 * - the whole site may send at most `sms.otp_daily_cap_global` codes a day;
 * - OtpService adds its own 60 second wait and 5 per hour.
 * The number is stored encrypted and counts as "taken" only after it is verified.
 */
final class StartPhoneVerification
{
    private const MEMBER_DAILY_LIMIT = 5;

    public function __construct(private readonly SmsOtpChannel $channel) {}

    /**
     * @throws ValidationException when the number is not usable or limits are hit
     * @throws OtpThrottledException when a code was requested too recently
     * @throws SmsDeliveryException when the provider did not take the message
     */
    public function handle(User $user, string $input): void
    {
        $phone = SmsNumber::normalize($input);

        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => __('sms.errors.invalid')]);
        }

        $hash = SmsNumber::hash($phone);

        // The same message for "already used" and "cannot be used", so nobody can
        // learn which numbers are registered.
        $takenByOther = User::query()
            ->where('phone_hash', $hash)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($takenByOther) {
            throw ValidationException::withMessages(['phone' => __('sms.errors.unavailable')]);
        }

        // Already verified on this account: nothing to do.
        if ($user->phone_hash === $hash && $user->phone_verified_at !== null) {
            return;
        }

        $this->guardLimits($user);

        // A different number starts again from "not verified".
        $user->forceFill([
            'phone' => $phone,
            'phone_hash' => null,
            'phone_verified_at' => null,
        ])->save();

        (new OtpService($this->channel))->issue($user, OtpPurpose::Phone);
    }

    private function guardLimits(User $user): void
    {
        $memberKey = 'phone-start:'.$user->getKey();
        $globalKey = 'phone-start:global';
        $globalMax = max(1, (int) config('lifepartner.sms.otp_daily_cap_global'));

        if (RateLimiter::tooManyAttempts($memberKey, self::MEMBER_DAILY_LIMIT)
            || RateLimiter::tooManyAttempts($globalKey, $globalMax)) {
            throw ValidationException::withMessages(['phone' => __('sms.errors.try_later')]);
        }

        RateLimiter::hit($memberKey, 86400);
        RateLimiter::hit($globalKey, 86400);
    }
}
