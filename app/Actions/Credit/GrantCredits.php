<?php

declare(strict_types=1);

namespace App\Actions\Credit;

use App\Enums\CreditReason;
use App\Models\CreditLedgerEntry;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Adds credits to a member's ledger, at most once per grant key.
 *
 * - A grant tied to a payment (purchase, membership_grant) gets the key
 *   "payment-{id}-{reason}", so running the same fulfilment twice, even at the same
 *   moment, adds the credits once. The database unique key is the final guard.
 * - Other grants may pass their own key (for example "free-user-5").
 * - The member's user row is locked while the grant is written.
 * - The activity log gets the reason, the number of credits and the payment code only.
 *
 * Calling it again with the same key returns the first entry and changes nothing.
 */
final class GrantCredits
{
    public function __invoke(
        User $user,
        int $credits,
        CreditReason $reason,
        ?Payment $payment = null,
        ?CarbonInterface $expiresAt = null,
        ?string $grantKey = null,
    ): CreditLedgerEntry {
        if ($credits < 1) {
            throw new InvalidArgumentException('Credits to grant must be at least 1.');
        }

        if (! $reason->isGrant()) {
            throw new InvalidArgumentException('This reason does not add credits.');
        }

        $key = $this->key($user, $reason, $payment, $grantKey);

        try {
            return DB::transaction(fn (): CreditLedgerEntry => $this->grant($user, $credits, $reason, $payment, $expiresAt, $key));
        } catch (UniqueConstraintViolationException $e) {
            $existing = $key === null ? null : $this->existing($key);

            if ($existing === null) {
                throw $e;
            }

            return $existing;
        }
    }

    private function grant(User $user, int $credits, CreditReason $reason, ?Payment $payment, ?CarbonInterface $expiresAt, ?string $key): CreditLedgerEntry
    {
        User::query()->whereKey($user->getKey())->lockForUpdate()->first();

        if ($key !== null && ($existing = $this->existing($key)) !== null) {
            return $existing;
        }

        $entry = new CreditLedgerEntry;
        $entry->forceFill([
            'user_id' => $user->getKey(),
            'delta' => $credits,
            'reason' => $reason->value,
            'payment_id' => $payment?->getKey(),
            'grant_key' => $key,
            'expires_at' => $expiresAt,
        ])->save();

        $properties = ['reason' => $reason->value, 'credits' => $credits];

        if ($payment !== null) {
            $properties['payment_code'] = $payment->code;
        }

        activity('billing')
            ->causedBy($user)
            ->withProperties($properties)
            ->log('credits.granted');

        return $entry;
    }

    private function key(User $user, CreditReason $reason, ?Payment $payment, ?string $grantKey): ?string
    {
        if ($reason->isPaymentBacked()) {
            if ($payment === null) {
                throw new InvalidArgumentException('This grant needs the payment it belongs to.');
            }

            if ((int) $payment->user_id !== (int) $user->getKey()) {
                throw new InvalidArgumentException('The payment belongs to another member.');
            }

            return 'payment-'.$payment->getKey().'-'.$reason->value;
        }

        if ($reason === CreditReason::FreeGrant && $grantKey === null) {
            throw new InvalidArgumentException('A free grant needs a grant key.');
        }

        return $grantKey;
    }

    private function existing(string $key): ?CreditLedgerEntry
    {
        return CreditLedgerEntry::query()->where('grant_key', $key)->first();
    }
}
