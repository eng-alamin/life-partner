<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BiodataStatus;
use App\Enums\HiddenSource;
use App\Models\Biodata;
use App\Enums\StaffPermission;
use App\Models\User;

/**
 * Rules for a member's OWN biodata. Viewing someone else's biodata is decided
 * later by BiodataVisibility (Module 4), never by this policy.
 */
class BiodataPolicy
{
    /** A member may create a biodata only if they do not have one yet. */
    public function create(User $user): bool
    {
        return $user->isActive() && ! $user->hasDeletionRequested() && ! $user->biodata()->exists();
    }

    public function view(User $user, Biodata $biodata): bool
    {
        return $this->owns($user, $biodata);
    }

    /**
     * Editing is blocked only while the biodata is deactivated. Editing an
     * approved biodata sends it back to review; editing a pending biodata keeps
     * it pending and raises its revision so a reviewer who already opened it
     * cannot approve the older version by mistake.
     */
    public function update(User $user, Biodata $biodata): bool
    {
        return $this->owns($user, $biodata)
            && $user->isActive()
            && ! $user->hasDeletionRequested()
            && $biodata->status->isMemberEditable();
    }

    /** Only a draft or a rejected biodata can be sent for review. */
    public function submit(User $user, Biodata $biodata): bool
    {
        return $this->owns($user, $biodata)
            && $user->isActive()
            && ! $user->hasDeletionRequested()
            && in_array($biodata->status, [BiodataStatus::Draft, BiodataStatus::Rejected], true);
    }

    /**
     * Staff review: needs the review permission, an active account, and the
     * biodata must belong to someone else (nobody approves their own biodata).
     */
    public function review(User $user, Biodata $biodata): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ReviewBiodata->value)
            && ! $this->owns($user, $biodata);
    }

    /** Upload, delete, choose the main photo, set photo visibility. Blocked only while deactivated. */
    public function managePhotos(User $user, Biodata $biodata): bool
    {
        return $this->owns($user, $biodata)
            && $user->isActive()
            && ! $user->hasDeletionRequested()
            && $biodata->status !== BiodataStatus::Deactivated;
    }

    /** Only an approved biodata can be hidden, and only by its owner. */
    public function hide(User $user, Biodata $biodata): bool
    {
        return $this->owns($user, $biodata)
            && $user->isActive()
            && $biodata->status === BiodataStatus::Approved;
    }

    /** The owner may show it again only if the owner hid it. A staff-hide (or an unknown source) is never undone by the owner. */
    public function unhide(User $user, Biodata $biodata): bool
    {
        return $this->owns($user, $biodata)
            && $user->isActive()
            && ! $user->hasDeletionRequested()
            && $biodata->status === BiodataStatus::Hidden
            && $biodata->hidden_source === HiddenSource::Owner;
    }

    /**
     * A member may pause an approved or pending biodata, or one they hid themselves.
     * A biodata hidden by staff (or with an unknown hide source) can not be paused: that would hide the staff hide.
     */
    public function deactivate(User $user, Biodata $biodata): bool
    {
        if (! $this->owns($user, $biodata) || ! $user->isActive()) {
            return false;
        }

        return match ($biodata->status) {
            BiodataStatus::Approved, BiodataStatus::Pending => true,
            BiodataStatus::Hidden => $biodata->hidden_source === HiddenSource::Owner,
            default => false,
        };
    }

    /** Only the owner, and only a deactivation the owner made. Not while an account deletion is waiting. */
    public function reactivate(User $user, Biodata $biodata): bool
    {
        return $this->owns($user, $biodata)
            && $user->isActive()
            && ! $user->hasDeletionRequested()
            && $biodata->status === BiodataStatus::Deactivated
            && $biodata->hidden_source === HiddenSource::Owner;
    }

    /** Staff restore: review permission, not their own biodata, not a biodata the member hid, and not the biodata of a member whose account deletion is waiting. */
    public function restoreHidden(User $user, Biodata $biodata): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ReviewBiodata->value)
            && ! $this->owns($user, $biodata)
            && $biodata->status === BiodataStatus::Hidden
            && $biodata->hidden_source !== HiddenSource::Owner
            && ! User::query()->whereKey($biodata->user_id)->whereNotNull('deletion_requested_at')->exists();
    }

    public function delete(User $user, Biodata $biodata): bool
    {
        return $this->owns($user, $biodata);
    }

    private function owns(User $user, Biodata $biodata): bool
    {
        return $user->getKey() === $biodata->user_id;
    }
}
