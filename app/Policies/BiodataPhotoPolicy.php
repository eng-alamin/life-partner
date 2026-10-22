<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\BiodataPhoto;
use App\Models\User;

/**
 * Staff review of a photo: needs the review permission and an active account,
 * and nobody reviews a photo of their own biodata. Who may SEE a photo is
 * decided by BiodataVisibility, not here.
 */
class BiodataPhotoPolicy
{
    public function review(User $user, BiodataPhoto $photo): bool
    {
        $photo->loadMissing('biodata');

        // A photo whose biodata was deleted cannot be reviewed.
        return $photo->biodata !== null
            && $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ReviewBiodata->value)
            && $photo->biodata->user_id !== $user->getKey();
    }
}
