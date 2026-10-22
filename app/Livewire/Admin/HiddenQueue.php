<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Biodata\RestoreHiddenBiodata;
use App\Enums\BiodataStatus;
use App\Enums\HiddenSource;
use App\Enums\StaffPermission;
use App\Models\Biodata;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Biodatas hidden by staff (or hidden before the source was recorded), newest first.
 * Biodatas a member hid themselves are never listed: that is the member's own choice.
 * A reviewer never sees their own biodata here.
 */
#[Layout('layouts::admin')]
class HiddenQueue extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    #[Locked]
    public string $notice = '';

    public function mount(): void
    {
        Gate::authorize(StaffPermission::ReviewBiodata->value);
    }

    /** The code comes from the browser, so the action checks its shape, loads the row itself and authorizes. */
    public function restore(string $code, RestoreHiddenBiodata $restore): void
    {
        Gate::authorize(StaffPermission::ReviewBiodata->value);

        $key = 'hidden-restore:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);

        $status = $restore(auth()->user(), $code);

        $this->notice = __('admin_hidden.restored.'.$status->value);
    }

    public function render()
    {
        $items = Biodata::query()
            ->where('status', BiodataStatus::Hidden->value)
            ->where(function ($query): void {
                $query->whereNull('hidden_source')
                    ->orWhere('hidden_source', HiddenSource::Staff->value);
            })
            ->where('user_id', '!=', auth()->id())
            ->with('presentDistrict')
            ->orderByDesc('hidden_at')
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE);

        return view('livewire.admin.hidden-queue', ['items' => $items])
            ->title(__('admin_hidden.title'));
    }
}
