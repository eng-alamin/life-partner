<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** The SMS could not be handed to the provider. The message never holds a number or a code. */
class SmsDeliveryException extends RuntimeException
{
    public function __construct(public readonly int $errorCode = 0)
    {
        parent::__construct('SMS could not be sent (code '.$errorCode.').');
    }
}
