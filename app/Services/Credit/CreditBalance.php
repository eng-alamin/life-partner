<?php

declare(strict_types=1);

namespace App\Services\Credit;

use App\Models\CreditLedgerEntry;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Computes a member's contact unlock credits from the ledger. Nothing is stored:
 * the answer always comes from the rows, so it cannot drift.
 *
 * Rules:
 * - A spend uses credits that expire first, then credits that never expire.
 * - A spend can only use credits that were still valid when the spend happened.
 * - Credits that expire unused are lost; what was spent stays spent.
 * - A reversal larger than what is left (for example after a refund) becomes a debt
 *   that later grants pay off first. The balance may then be negative: it is shown as
 *   it is and never silently raised to zero.
 */
final class CreditBalance
{
    public function for(User $user): int
    {
        $now = now();
        $lots = [];
        $debt = 0;

        $rows = CreditLedgerEntry::query()
            ->where('user_id', $user->getKey())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'delta', 'expires_at', 'created_at']);

        foreach ($rows as $row) {
            $delta = (int) $row->delta;

            if ($delta > 0) {
                $left = $delta;

                if ($debt > 0) {
                    $paid = min($debt, $left);
                    $debt -= $paid;
                    $left -= $paid;
                }

                if ($left > 0) {
                    $lots[] = ['left' => $left, 'expires' => $row->expires_at];
                }

                continue;
            }

            $need = -$delta;
            $need = $this->takeFromLots($lots, $need, $row->created_at);
            $debt += $need;
        }

        $valid = 0;

        foreach ($lots as $lot) {
            if ($lot['expires'] === null || $lot['expires']->greaterThan($now)) {
                $valid += $lot['left'];
            }
        }

        return $valid - $debt;
    }

    /**
     * Takes credits out of the lots that were valid at $at, earliest expiry first
     * and never-expiring lots last. Returns the part that could not be covered.
     *
     * @param  list<array{left: int, expires: CarbonInterface|null}>  $lots
     */
    private function takeFromLots(array &$lots, int $need, CarbonInterface $at): int
    {
        $order = array_keys($lots);

        usort($order, function (int $a, int $b) use ($lots): int {
            $left = $lots[$a]['expires']?->getTimestamp() ?? PHP_INT_MAX;
            $right = $lots[$b]['expires']?->getTimestamp() ?? PHP_INT_MAX;

            return $left <=> $right ?: $a <=> $b;
        });

        foreach ($order as $index) {
            if ($need === 0) {
                break;
            }

            $expires = $lots[$index]['expires'];

            if ($expires !== null && $expires->lessThanOrEqualTo($at)) {
                continue;
            }

            $take = min($lots[$index]['left'], $need);
            $lots[$index]['left'] -= $take;
            $need -= $take;
        }

        return $need;
    }
}
