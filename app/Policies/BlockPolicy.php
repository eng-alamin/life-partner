<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Biodata;
use App\Models\User;

/** Who may block whom. Removing one's own block needs no policy: UnblockMember only touches the caller's own rows. */
class BlockPolicy
{
    /** An active member with a verified email may block another member, never themselves. */
    public function create(User $user, Biodata $target): bool
    {
        return $user->isActive()
            && $user->hasVerifiedEmail()
            && (int) $user->getKey() !== (int) $target->user_id;
    }
}
