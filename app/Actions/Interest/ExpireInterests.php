<?php

declare(strict_types=1);

namespace App\Actions\Interest;

use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use App\Models\Interest;
use App\Services\Interest\InterestTransitions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Closes pending interests whose expiry time has passed. Safe to run often:
 * each row is locked and checked again, so a request that was answered a
 * moment ago is left alone. Returns how many requests were expired.
 */
final class ExpireInterests
{
    public function __construct(private readonly InterestTransitions $transitions) {}

    public function __invoke(): int
    {
        $expired = 0;

        Interest::query()
            ->where('status', InterestStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(200, function (Collection $rows) use (&$expired): void {
                foreach ($rows as $row) {
                    DB::transaction(function () use ($row, &$expired): void {
                        $interest = Interest::query()->whereKey($row->getKey())->lockForUpdate()->first();

                        if ($interest === null || $interest->status !== InterestStatus::Pending) {
                            return;
                        }

                        $this->transitions->move($interest, InterestStatus::Expired, InterestActor::System);
                        $expired++;
                    });
                }
            });

        return $expired;
    }
}
