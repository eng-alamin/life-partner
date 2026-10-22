<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

/**
 * Tells staff why a due account deletion is waiting, for a whole page of members at once
 * (three queries in total, never one per member).
 *
 * These are the same two rules PurgeDeletedAccount uses: an open report against the member's
 * biodata, or a payment that is still pending or under review. If one of those rules ever
 * changes, change it in both places.
 */
final class DeletionPauseReasons
{
    public const REPORT = 'report';

    public const PAYMENT = 'payment';

    /**
     * @param  list<int>  $userIds
     * @return array<int, list<string>> user id => reasons (only users that have one)
     */
    public function for(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $reasons = [];

        // Biodata of these users (soft-deleted ones are ignored, as the purge ignores them).
        $biodataToUser = DB::table('biodatas')
            ->whereIn('user_id', $userIds)
            ->whereNull('deleted_at')
            ->pluck('user_id', 'id');

        if ($biodataToUser->isNotEmpty()) {
            $reported = DB::table('reports')
                ->whereIn('biodata_id', $biodataToUser->keys()->all())
                ->where('status', 'open')
                ->distinct()
                ->pluck('biodata_id');

            foreach ($reported as $biodataId) {
                $reasons[(int) $biodataToUser[$biodataId]][] = self::REPORT;
            }
        }

        $unfinished = DB::table('payments')
            ->whereIn('user_id', $userIds)
            ->whereIn('status', ['pending', 'under_review'])
            ->distinct()
            ->pluck('user_id');

        foreach ($unfinished as $userId) {
            $reasons[(int) $userId][] = self::PAYMENT;
        }

        return $reasons;
    }
}
