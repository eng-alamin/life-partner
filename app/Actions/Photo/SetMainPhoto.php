<?php

declare(strict_types=1);

namespace App\Actions\Photo;

use App\Enums\PhotoStatus;
use App\Models\Biodata;
use App\Models\BiodataPhoto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Chooses which of the member's own photos is the main one. */
final class SetMainPhoto
{
    public function __invoke(User $user, int $photoId): void
    {
        $biodata = Biodata::query()->where('user_id', $user->getKey())->firstOrFail();

        Gate::forUser($user)->authorize('managePhotos', $biodata);

        $photo = $biodata->photos()->whereKey($photoId)->firstOrFail();

        if ($photo->status === PhotoStatus::Rejected) {
            throw ValidationException::withMessages(['upload' => __('photos.errors.rejected_main')]);
        }

        DB::transaction(function () use ($biodata, $photo): void {
            BiodataPhoto::query()->where('biodata_id', $biodata->getKey())->update(['is_main' => false]);
            $photo->forceFill(['is_main' => true])->save();
        });
    }
}
