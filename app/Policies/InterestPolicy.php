<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BiodataStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;

/**
 * Who may send, answer or withdraw an interest. The relations `sender` and
 * `receiver` must be loaded on the interest (the actions do this).
 *
 * The state of the request (pending, expired) and the sending limits are
 * checked by the actions, not here.
 */
class InterestPolicy
{
    /** Sending needs an active member with a verified email, no waiting account deletion and an approved biodata of their own. */
    public function send(User $user, Biodata $sender): bool
    {
        return $user->isActive()
            && $user->hasVerifiedEmail()
            && ! $user->hasDeletionRequested()
            && $user->getKey() === $sender->user_id
            && $sender->status === BiodataStatus::Approved;
    }

    /**
     * Only the owner of the receiving biodata, and not while that member is paused
     * (biodata deactivated or hidden, or an account deletion waits). The rule is the
     * same as for revealContact().
     */
    public function respond(User $user, Interest $interest): bool
    {
        return $user->isActive()
            && $user->getKey() === $interest->receiver->user_id
            && ! $this->isPaused($user, $interest->receiver);
    }

    /**
     * Only the receiver may create the guardian link, and only for an accepted
     * request that still waits for the guardian. (The action checks the state.)
     */
    public function issueGuardianLink(User $user, Interest $interest): bool
    {
        return $this->respond($user, $interest);
    }

    /**
     * Either side may ask, but only for an interest of their own biodata, and not
     * while that biodata is deactivated or hidden, or an account deletion waits.
     * The state of the request and what is
     * shown are decided by ContactReveal.
     */
    public function revealContact(User $user, Interest $interest, Biodata $own): bool
    {
        return $user->isActive()
            && $user->getKey() === $own->user_id
            && ! $this->isPaused($user, $own)
            && in_array((int) $own->getKey(), [(int) $interest->sender_biodata_id, (int) $interest->receiver_biodata_id], true);
    }

    /**
     * The one pause rule for a member's own biodata: deactivated, hidden (by the
     * owner or by staff), or an account deletion is waiting. Keep it in step with
     * BiodataVisibility::isPaused().
     */
    private function isPaused(User $user, Biodata $own): bool
    {
        return $user->hasDeletionRequested()
            || in_array($own->status, [BiodataStatus::Deactivated, BiodataStatus::Hidden], true);
    }

    /** Only the owner of the sending biodata. */
    public function cancel(User $user, Interest $interest): bool
    {
        return $user->isActive()
            && $user->getKey() === $interest->sender->user_id;
    }
}
