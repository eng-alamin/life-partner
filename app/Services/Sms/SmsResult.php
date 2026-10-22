<?php

declare(strict_types=1);

namespace App\Services\Sms;

/** What the provider answered for one SMS. */
final class SmsResult
{
    /** Our own codes (the provider's codes are positive numbers). */
    public const NOT_CONFIGURED = -1;

    public const NETWORK = -2;

    public const BAD_RESPONSE = -3;

    /** The provider's code for "not enough balance". */
    public const INSUFFICIENT_BALANCE = 417;

    private function __construct(
        public readonly bool $ok,
        public readonly ?string $reference,
        public readonly int $errorCode,
        public readonly bool $retryable,
    ) {}

    public static function sent(?string $reference = null): self
    {
        return new self(true, $reference === null ? null : mb_substr($reference, 0, 40), 0, false);
    }

    public static function failed(int $errorCode, bool $retryable): self
    {
        return new self(false, null, $errorCode, $retryable);
    }
}
