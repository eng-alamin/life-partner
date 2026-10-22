<?php

declare(strict_types=1);

namespace App\Services\Otp;

use App\Enums\OtpPurpose;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailOtpChannel implements OtpChannel
{
    public function name(): string
    {
        return 'email';
    }

    public function send(User $user, string $code, OtpPurpose $purpose): void
    {
        Mail::to($user->email)
            ->locale($user->locale ?: config('lifepartner.default_locale'))
            ->queue(new OtpMail($user->name, $code, $purpose, OtpService::TTL_MINUTES));
    }
}
