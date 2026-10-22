<?php

declare(strict_types=1);

namespace App\Enums;

enum OtpResult
{
    case Verified;
    case Invalid;
    case Expired;
    case TooManyAttempts;
}
