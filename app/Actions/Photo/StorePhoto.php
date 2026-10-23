<?php

declare(strict_types=1);

namespace App\Actions\Photo;

use App\Enums\NotificationType;
use App\Enums\PhotoStatus;
use App\Models\Biodata;
use App\Models\BiodataPhoto;
use App\Models\User;
use App\Services\Notification\Notifier;
use App\Services\Photo\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Adds a photo to the signed-in member's own biodata. The photo starts as
 * pending, so nobody else can see it until staff approve it. The biodata is
 * found from the signed-in user, never from an id sent by the browser.
 */
final class StorePhoto
{
    public const MAX_PER_BIODATA = 3;

    public function __construct(
        private readonly ImageProcessor $images,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $user, UploadedFile $file): BiodataPhoto
    {
        $biodata = Biodata::query()->where('user_id', $user->getKey())->firstOrFail();

        Gate::forUser($user)->authorize('managePhotos', $biodata);

        // Cheap early check; the real check runs again under a row lock below.
        $this->assertRoom($biodata->photos()->count());

        $processed = $this->images->process($file);

        $code = Str::lower(Str::random(24));
        $path = $biodata->public_code.'/'.$code.'-large.jpg';
        $thumbPath = $biodata->public_code.'/'.$code.'-thumb.jpg';

        $disk = Storage::disk('photos');
        $disk->put($path, $processed['large']);
        $disk->put($thumbPath, $processed['thumb']);

        try {
            $photo = DB::transaction(function () use ($biodata, $code, $path, $thumbPath): BiodataPhoto {
                $locked = Biodata::query()->lockForUpdate()->findOrFail($biodata->getKey());

                $count = $locked->photos()->count();
                $this->assertRoom($count);

                $photo = new BiodataPhoto;
                $photo->forceFill([
                    'biodata_id' => $locked->getKey(),
                    'code' => $code,
                    'path' => $path,
                    'thumb_path' => $thumbPath,
                    'is_main' => ! $locked->photos()->where('is_main', true)->exists(),
                    'status' => PhotoStatus::Pending->value,
                    'sort_order' => $count,
                ])->save();

                return $photo;
            });
        } catch (Throwable $exception) {
            $disk->delete([$path, $thumbPath]);

            throw $exception;
        }

        $this->notifier->toStaff(NotificationType::StaffPhotoWaiting);

        return $photo;
    }

    private function assertRoom(int $count): void
    {
        if ($count >= self::MAX_PER_BIODATA) {
            throw ValidationException::withMessages([
                'upload' => __('photos.errors.max_photos', ['max' => self::MAX_PER_BIODATA]),
            ]);
        }
    }
}
