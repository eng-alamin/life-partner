<?php

declare(strict_types=1);

namespace App\Services\Otp;

use App\Enums\OtpPurpose;
use App\Models\User;

/**
 * A way of delivering a one-time code. Email is the only channel today;
 * an SMS channel can be added later without touching OtpService.
 */
interface OtpChannel
{
    /** Stored in otp_codes.channel (for example "email"). */
    public function name(): string;

    public function send(User $user, string $code, OtpPurpose $purpose): void;
}
