<?php

declare(strict_types=1);

namespace App\Services\Payment;

use RuntimeException;

/**
 * A bKash call failed. The message is a short code of ours ("network", "auth", "bad_response" ...),
 * never text copied from bKash, a token or a secret, so it is safe to log and to store.
 * $bkashCode is bKash's own statusCode when it sent one.
 */
class BkashException extends RuntimeException
{
    public function __construct(string $reason, public readonly ?string $bkashCode = null)
    {
        parent::__construct($reason);
    }
}
