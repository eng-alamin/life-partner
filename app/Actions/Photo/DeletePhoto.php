<?php

declare(strict_types=1);

namespace App\Actions\Photo;

use App\Models\Biodata;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes one of the signed-in member's own photos and its files. The photo is
 * looked up inside the member's own biodata, so another member's id finds
 * nothing (404). If the main photo goes, another photo becomes the main one.
 */
final class DeletePhoto
{
    public function __invoke(User $user, int $photoId): void
    {
        $biodata = Biodata::query()->where('user_id', $user->getKey())->firstOrFail();

        Gate::forUser($user)->authorize('managePhotos', $biodata);

        $photo = $biodata->photos()->whereKey($photoId)->firstOrFail();
        $files = $photo->filePaths();

        DB::transaction(function () use ($biodata, $photo): void {
            $wasMain = $photo->is_main;

            $photo->delete();

            if ($wasMain) {
                // An approved photo is preferred, then the oldest one.
                $next = $biodata->photos()
                    ->reorder()
                    ->orderByRaw("CASE WHEN status = 'approved' THEN 0 ELSE 1 END")
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                $next?->forceFill(['is_main' => true])->save();
            }
        });

        Storage::disk('photos')->delete($files);
    }
}
