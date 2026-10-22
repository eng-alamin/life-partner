<?php

declare(strict_types=1);

namespace App\Actions\Photo;

use App\Enums\PhotoVisibility;
use App\Models\Biodata;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Sets who may see the member's approved photos: nobody, members of the
 * opposite type, or only members whose interest with this member was accepted
 * (and approved by the guardian when that applies). The rule itself is checked
 * on every photo request by BiodataVisibility, not here.
 */
final class UpdatePhotoVisibility
{
    /** @var list<PhotoVisibility> */
    public const SELECTABLE = [PhotoVisibility::Hidden, PhotoVisibility::AfterAcceptance, PhotoVisibility::ApprovedMembers];

    public function __invoke(User $user, PhotoVisibility $visibility): Biodata
    {
        $biodata = Biodata::query()->where('user_id', $user->getKey())->firstOrFail();

        Gate::forUser($user)->authorize('managePhotos', $biodata);

        if (! in_array($visibility, self::SELECTABLE, true)) {
            throw ValidationException::withMessages(['visibility' => __('photos.errors.visibility_invalid')]);
        }

        $biodata->forceFill(['photo_visibility' => $visibility->value])->save();

        return $biodata;
    }
}
