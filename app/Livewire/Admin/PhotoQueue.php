<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Photo\ReviewPhoto;
use App\Enums\PhotoStatus;
use App\Enums\StaffPermission;
use App\Models\BiodataPhoto;
use App\Services\Search\BiodataVisibility;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** Pending photos, oldest first. A reviewer never sees photos of their own biodata. */
#[Layout('layouts::admin')]
class PhotoQueue extends Component
{
    use WithPagination;

    private const PER_PAGE = 12;

    /** @var array<int, string> rejection reasons typed by the reviewer, keyed by photo id */
    public array $reasons = [];

    public string $notice = '';

    public function mount(): void
    {
        Gate::authorize(StaffPermission::ReviewBiodata->value);
    }

    public function approve(ReviewPhoto $review, int $photoId): void
    {
        $this->notice = '';
        $this->throttle();

        $review->approve(auth()->user(), $photoId);

        unset($this->reasons[$photoId]);
        $this->notice = __('photos.admin.approved');
    }

    public function reject(ReviewPhoto $review, int $photoId): void
    {
        $this->notice = '';
        $this->throttle();

        try {
            $review->reject(auth()->user(), $photoId, (string) ($this->reasons[$photoId] ?? ''));
        } catch (ValidationException $exception) {
            // Show the reason error next to the right photo.
            if (isset($exception->errors()['rejectReason'])) {
                throw ValidationException::withMessages(['reasons.'.$photoId => $exception->errors()['rejectReason'][0]]);
            }

            throw $exception;
        }

        unset($this->reasons[$photoId]);
        $this->notice = __('photos.admin.rejected');
    }

    public function render(BiodataVisibility $visibility)
    {
        $paginator = BiodataPhoto::query()
            ->where('status', PhotoStatus::Pending->value)
            ->whereHas('biodata', fn ($query) => $query->where('user_id', '!=', auth()->id()))
            ->with('biodata:id,public_code,type')
            ->orderBy('created_at')
            ->orderBy('id')
            ->simplePaginate(self::PER_PAGE);

        $items = collect($paginator->items())->map(fn (BiodataPhoto $photo): array => [
            'id' => $photo->id,
            'biodata_code' => $photo->biodata->public_code,
            'type' => $photo->biodata->type?->label(),
            'url' => $visibility->photoUrl($photo->biodata, $photo, 'large'),
            'uploaded' => $photo->created_at?->diffForHumans(),
        ])->all();

        return view('livewire.admin.photo-queue', ['items' => $items, 'paginator' => $paginator])
            ->title(__('photos.admin.title'));
    }

    private function throttle(): void
    {
        $key = 'photo-review:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 200)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
