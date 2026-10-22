<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class OtpThrottledException extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('Too many verification codes requested.');
    }
}
