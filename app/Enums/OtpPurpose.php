<?php

declare(strict_types=1);

namespace App\Enums;

enum OtpPurpose: string
{
    case Verify = 'verify';
    case Reset = 'reset';
    case Link = 'link';
    /** Confirms a mobile number by SMS. */
    case Phone = 'phone';
}
