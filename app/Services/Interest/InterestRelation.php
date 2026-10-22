<?php

declare(strict_types=1);

namespace App\Services\Interest;

use App\Enums\BiodataStatus;
use App\Enums\InterestPanelState;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;

/**
 * Works out what the interest panel of a profile page should offer. It only
 * decides what to show; SendInterest still checks every rule when the member
 * presses the button.
 */
final class InterestRelation
{
    public function stateFor(User $viewer, Biodata $target): InterestPanelState
    {
        $own = Biodata::query()->where('user_id', $viewer->getKey())->first();

        if ($own === null) {
            return InterestPanelState::NoBiodata;
        }

        if ((int) $own->getKey() === (int) $target->getKey()) {
            return InterestPanelState::Own;
        }

        if (! $viewer->isActive() || $own->status !== BiodataStatus::Approved) {
            return InterestPanelState::NotApproved;
        }

        $ownId = (int) $own->getKey();
        $targetId = (int) $target->getKey();

        $rows = Interest::query()
            ->whereIn('status', [InterestStatus::Pending->value, InterestStatus::Accepted->value])
            ->where(function ($query) use ($ownId, $targetId): void {
                $query->where(fn ($q) => $q->where('sender_biodata_id', $ownId)->where('receiver_biodata_id', $targetId))
                    ->orWhere(fn ($q) => $q->where('sender_biodata_id', $targetId)->where('receiver_biodata_id', $ownId));
            })
            ->get(['sender_biodata_id', 'status']);

        if ($rows->contains(fn (Interest $row): bool => $row->status === InterestStatus::Accepted)) {
            return InterestPanelState::Connected;
        }

        if ($rows->contains(fn (Interest $row): bool => (int) $row->sender_biodata_id === $ownId)) {
            return InterestPanelState::Sent;
        }

        return $rows->isNotEmpty() ? InterestPanelState::Received : InterestPanelState::CanSend;
    }
}
