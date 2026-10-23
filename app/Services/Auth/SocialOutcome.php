<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

final class SocialOutcome
{
    private function __construct(
        public readonly ?User $user,
        public readonly bool $needsEmailProof,
        public readonly bool $registrationClosed = false,
    ) {}

    public static function login(User $user): self
    {
        return new self($user, false);
    }

    public static function needsEmailProof(): self
    {
        return new self(null, true);
    }

    /** Registration is switched off and this sign-in would create a new account. */
    public static function registrationClosed(): self
    {
        return new self(null, false, true);
    }

    /** The account is suspended or deleted. */
    public static function unavailable(): self
    {
        return new self(null, false);
    }
}
