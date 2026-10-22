<?php

declare(strict_types=1);

namespace App\Actions\Credit;

use App\Enums\CreditReason;
use App\Models\CreditLedgerEntry;
use App\Models\User;

/**
 * Gives a member the free contact unlocks once (the number is the setting
 * lifepartner.billing.free_unlocks). Safe to call many times: the grant key
 * "free-user-{id}" means the member can only ever receive it once.
 */
final class GrantFreeCredits
{
    public function __construct(private readonly GrantCredits $grant) {}

    public function __invoke(User $user): ?CreditLedgerEntry
    {
        $free = (int) config('lifepartner.billing.free_unlocks');

        if ($free < 1) {
            return null;
        }

        return ($this->grant)($user, $free, CreditReason::FreeGrant, null, null, 'free-user-'.$user->getKey());
    }
}
