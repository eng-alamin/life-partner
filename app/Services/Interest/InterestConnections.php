<?php

declare(strict_types=1);

namespace App\Services\Interest;

use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use App\Models\Interest;
use App\Services\Block\BlockGuard;

/**
 * Are two biodatas connected? That means an accepted interest between them, in
 * either direction, with guardian status "not_required" or "approved".
 *
 * This is the same rule as ContactReveal::isOpen(); a test keeps the two in
 * step. Used for the "visible after acceptance" photo option.
 */
final class InterestConnections
{
    public function __construct(private readonly BlockGuard $blocks) {}

    public function areConnected(int $biodataA, int $biodataB): bool
    {
        if ($biodataA === $biodataB) {
            return false;
        }

        // A block closes the connection at once (photos and contact), whatever the interest status.
        if ($this->blocks->betweenBiodatas($biodataA, $biodataB)) {
            return false;
        }

        return Interest::query()
            ->where('status', InterestStatus::Accepted->value)
            ->whereIn('guardian_status', [GuardianApprovalStatus::NotRequired->value, GuardianApprovalStatus::Approved->value])
            ->where(function ($query) use ($biodataA, $biodataB): void {
                $query->where(fn ($pair) => $pair->where('sender_biodata_id', $biodataA)->where('receiver_biodata_id', $biodataB))
                    ->orWhere(fn ($pair) => $pair->where('sender_biodata_id', $biodataB)->where('receiver_biodata_id', $biodataA));
            })
            ->exists();
    }
}
