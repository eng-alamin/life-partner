<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\IdentityVerification;
use App\Models\User;

/**
 * Only an active account holding the identity.review permission (admin) may open the
 * identity queue, open a request, look at the pictures and decide. Nobody works on
 * their own request. Members never open the pictures again after sending them.
 */
class IdentityVerificationPolicy
{
    /** Open the identity queue. */
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ReviewIdentity->value);
    }

    /** Open one request and decide it (approve or reject). Never one's own. */
    public function review(User $user, IdentityVerification $verification): bool
    {
        return $this->viewAny($user)
            && (int) $verification->user_id !== (int) $user->getKey();
    }

    /** Open a picture: only while the request waits and its files still exist. */
    public function viewFile(User $user, IdentityVerification $verification): bool
    {
        return $this->review($user, $verification)
            && $verification->isPending()
            && $verification->files_deleted_at === null;
    }
}
