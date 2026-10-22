<?php

declare(strict_types=1);

namespace App\Actions\Credit;

use App\Enums\CreditReason;
use App\Models\Biodata;
use App\Models\ContactUnlock;
use App\Models\CreditLedgerEntry;
use App\Models\Interest;
use App\Models\User;
use App\Services\Credit\CreditBalance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Spends one credit to unlock the contact details of one interest.
 *
 * - Only a member whose biodata is on one side of the interest may unlock it.
 * - One credit per interest, once: a second call for the same interest costs nothing
 *   and returns the first unlock (also guaranteed by the unique (user, interest) key).
 * - The member's user row is locked while the balance is checked and the spend is
 *   written, so two quick taps cannot spend the same credit twice or go below zero.
 * - The free unlocks are granted on first use (once).
 * - This action only handles credits. Whether the contact may be shown at all
 *   (accepted interest, guardian approval, blocks) is decided by RevealContact.
 */
final class SpendCredit
{
    public function __construct(
        private readonly CreditBalance $balance,
        private readonly GrantFreeCredits $freeCredits,
    ) {}

    public function __invoke(User $user, Interest $interest): ContactUnlock
    {
        try {
            return DB::transaction(fn (): ContactUnlock => $this->spend($user, $interest));
        } catch (UniqueConstraintViolationException $e) {
            $existing = $this->existing($user, $interest);

            if ($existing === null) {
                throw $e;
            }

            return $existing;
        }
    }

    private function spend(User $user, Interest $interest): ContactUnlock
    {
        User::query()->whereKey($user->getKey())->lockForUpdate()->first();

        $this->assertParticipant($user, $interest);

        $existing = $this->existing($user, $interest);

        if ($existing !== null) {
            return $existing;
        }

        ($this->freeCredits)($user);

        if ($this->balance->for($user) < 1) {
            throw ValidationException::withMessages(['form' => __('billing.errors.no_credit')]);
        }

        $entry = new CreditLedgerEntry;
        $entry->forceFill([
            'user_id' => $user->getKey(),
            'delta' => -1,
            'reason' => CreditReason::Unlock->value,
            'interest_id' => $interest->getKey(),
        ])->save();

        $unlock = new ContactUnlock;
        $unlock->forceFill([
            'user_id' => $user->getKey(),
            'interest_id' => $interest->getKey(),
            'ledger_id' => $entry->getKey(),
        ])->save();

        activity('billing')
            ->causedBy($user)
            ->withProperties(['interest_code' => $interest->code])
            ->log('contact.unlocked');

        return $unlock;
    }

    private function assertParticipant(User $user, Interest $interest): void
    {
        $takesPart = Biodata::query()
            ->where('user_id', $user->getKey())
            ->whereIn('id', [$interest->sender_biodata_id, $interest->receiver_biodata_id])
            ->exists();

        if (! $takesPart) {
            throw new AuthorizationException;
        }
    }

    private function existing(User $user, Interest $interest): ?ContactUnlock
    {
        return ContactUnlock::query()
            ->where('user_id', $user->getKey())
            ->where('interest_id', $interest->getKey())
            ->first();
    }
}
