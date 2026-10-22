<?php

declare(strict_types=1);

namespace App\Livewire\Biodata;

use App\Actions\Photo\DeletePhoto;
use App\Actions\Photo\SetMainPhoto;
use App\Actions\Photo\StorePhoto;
use App\Actions\Photo\UpdatePhotoVisibility;
use App\Enums\PhotoStatus;
use App\Enums\PhotoVisibility;
use App\Models\Biodata;
use App\Services\Search\BiodataVisibility;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The member's own photo panel on the overview page. It always works on the
 * signed-in member's own biodata; photo ids from the browser are only looked up
 * inside that biodata by the actions. Every action authorizes on the server.
 */
class MyPhotos extends Component
{
    use WithFileUploads;

    /** The chosen file (a Livewire temporary upload). */
    public $upload = null;

    public string $visibility = 'hidden';

    public string $notice = '';

    public function mount(): void
    {
        $biodata = $this->current();

        Gate::authorize('managePhotos', $biodata);

        $this->visibility = $biodata->photo_visibility->value;
    }

    public function save(StorePhoto $store): void
    {
        $this->notice = '';
        $this->throttle('photo-upload', 20);

        $this->validate([
            'upload' => [
                'required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp',
                'dimensions:min_width=300,min_height=300,max_width=5000,max_height=5000',
            ],
        ], [
            'upload.required' => __('photos.errors.required'),
            'upload.file' => __('photos.errors.unreadable'),
            'upload.uploaded' => __('photos.errors.upload_failed'),
            'upload.max' => __('photos.errors.too_big'),
            'upload.mimes' => __('photos.errors.type'),
            'upload.dimensions' => __('photos.errors.dimensions'),
        ]);

        $store(auth()->user(), $this->upload);

        $this->reset('upload');
        $this->notice = __('photos.uploaded');
    }

    public function delete(DeletePhoto $delete, int $photoId): void
    {
        $this->notice = '';
        $this->throttle('photo-change', 60);

        $delete(auth()->user(), $photoId);

        $this->notice = __('photos.deleted');
    }

    public function makeMain(SetMainPhoto $setMain, int $photoId): void
    {
        $this->notice = '';
        $this->throttle('photo-change', 60);

        $setMain(auth()->user(), $photoId);

        $this->notice = __('photos.main_set');
    }

    public function saveVisibility(UpdatePhotoVisibility $update): void
    {
        $this->notice = '';
        $this->throttle('photo-change', 60);

        $value = PhotoVisibility::tryFrom($this->visibility);

        if ($value === null) {
            throw ValidationException::withMessages(['visibility' => __('photos.errors.visibility_invalid')]);
        }

        $update(auth()->user(), $value);

        $this->notice = __('photos.visibility_saved');
    }

    public function render(BiodataVisibility $visibility)
    {
        $biodata = $this->current();

        $photos = $biodata->photos->map(fn ($photo): array => [
            'id' => $photo->id,
            'code' => $photo->code,
            'status' => $photo->status,
            'is_main' => (bool) $photo->is_main,
            'reason' => $photo->status === PhotoStatus::Rejected ? $photo->rejection_reason : null,
            'thumb' => $photo->status === PhotoStatus::Rejected ? null : $visibility->photoUrl($biodata, $photo, 'thumb'),
        ])->all();

        return view('livewire.biodata.my-photos', [
            'photos' => $photos,
            'max' => StorePhoto::MAX_PER_BIODATA,
            'canUpload' => count($photos) < StorePhoto::MAX_PER_BIODATA,
            'visibilityOptions' => array_map(
                fn (PhotoVisibility $case): array => ['value' => $case->value, 'label' => $case->label()],
                UpdatePhotoVisibility::SELECTABLE,
            ),
        ]);
    }

    private function current(): Biodata
    {
        return Biodata::query()
            ->where('user_id', auth()->id())
            ->with('photos')
            ->firstOrFail();
    }

    private function throttle(string $name, int $max): void
    {
        $key = $name.':'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages([
                'upload' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
