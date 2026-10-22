<?php

declare(strict_types=1);

namespace App\Services\Interest;

use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Services\Block\BlockGuard;

/**
 * The single place that decides whether the other side's guardian contact may
 * be shown, and what exactly is shown.
 *
 * Rules: the interest is accepted AND the guardian status is "not_required" or
 * "approved"; the other biodata still exists and is approved; and the other
 * side has a guardian record. Only the guardian's name, relation and phone are
 * ever shown. A member's own email and phone are never revealed.
 *
 * The interest needs `sender.guardian` and `receiver.guardian` loaded.
 */
final class ContactReveal
{
    public function __construct(private readonly BlockGuard $blocks) {}

    public function isOpen(Interest $interest): bool
    {
        return $interest->status === InterestStatus::Accepted
            && in_array($interest->guardian_status, [GuardianApprovalStatus::NotRequired, GuardianApprovalStatus::Approved], true);
    }

    /** @return array{name: string, relation: string, phone: string}|null */
    public function detailsFor(Interest $interest, Biodata $own): ?array
    {
        if (! $this->isOpen($interest)) {
            return null;
        }

        $ownId = (int) $own->getKey();

        if ($ownId === (int) $interest->sender_biodata_id) {
            $other = $interest->receiver;
        } elseif ($ownId === (int) $interest->receiver_biodata_id) {
            $other = $interest->sender;
        } else {
            return null;
        }

        if ($other === null || ! $other->status->isSearchable() || ! $other->ownerIsActive()) {
            return null;
        }

        // A block between the two members closes the contact at once.
        if ($this->blocks->between((int) $own->user_id, (int) $other->user_id)) {
            return null;
        }

        $guardian = $other->guardian;

        if ($guardian === null || blank($guardian->name) || blank($guardian->phone)) {
            return null;
        }

        return [
            'name' => (string) $guardian->name,
            'relation' => (string) ($guardian->relation?->label() ?? ''),
            'phone' => (string) $guardian->phone,
        ];
    }
}
