<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Throwable;

/**
 * Finds every account whose waiting period is over and purges them one at a time.
 * One failing account never stops the others: it is reported and tried again next run.
 */
final class PurgeDeletedAccounts
{
    public function __construct(private readonly PurgeDeletedAccount $purge) {}

    /** @return array{purged: int, paused: int, skipped: int, failed: int} */
    public function __invoke(): array
    {
        $counts = ['purged' => 0, 'paused' => 0, 'skipped' => 0, 'failed' => 0];

        $ids = User::query()
            ->whereNotNull('deletion_requested_at')
            ->where('deletion_scheduled_for', '<=', now())
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            try {
                $result = ($this->purge)((int) $id);
            } catch (Throwable $e) {
                report($e);
                $counts['failed']++;

                continue;
            }

            match ($result) {
                PurgeDeletedAccount::PURGED => $counts['purged']++,
                PurgeDeletedAccount::SKIPPED => $counts['skipped']++,
                default => $counts['paused']++,
            };
        }

        return $counts;
    }
}
