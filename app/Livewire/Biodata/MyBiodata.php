<?php

declare(strict_types=1);

namespace App\Livewire\Biodata;

use App\Actions\Biodata\DeactivateBiodata;
use App\Actions\Biodata\HideBiodata;
use App\Actions\Biodata\ReactivateBiodata;
use App\Actions\Biodata\SubmitBiodata;
use App\Actions\Biodata\UnhideBiodata;
use App\Enums\BiodataStatus;
use App\Enums\DeactivationReason;
use App\Enums\HiddenSource;
use App\Models\Biodata;
use App\Services\Biodata\BiodataProgress;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Overview of the signed-in member's own biodata: status, completion and the
 * edit / send-for-review buttons. It never takes an id from the browser and
 * opening it never creates a record.
 */
#[Layout('layouts::app')]
class MyBiodata extends Component
{
    /** Untrusted: checked against DeactivationReason inside the action. */
    public string $reason = '';

    public bool $confirmingDeactivate = false;

    public function mount(): void
    {
        $biodata = $this->current();

        if ($biodata !== null) {
            Gate::authorize('view', $biodata);
        }
    }

    public function submit(SubmitBiodata $submit): void
    {
        $key = 'biodata-submit:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);

        $submit(auth()->user());

        session()->flash('status', __('biodata.submitted'));
        $this->redirectRoute('my-biodata');
    }

    public function hide(HideBiodata $hide): void
    {
        $this->throttleVisibility();

        $hide(auth()->user());

        session()->flash('status', __('visibility.flash.hidden'));
        $this->redirectRoute('my-biodata');
    }

    public function unhide(UnhideBiodata $unhide): void
    {
        $this->throttleVisibility();

        $status = $unhide(auth()->user());

        session()->flash('status', $status === BiodataStatus::Approved
            ? __('visibility.flash.shown')
            : __('visibility.flash.sent_for_review'));
        $this->redirectRoute('my-biodata');
    }

    public function startDeactivate(): void
    {
        $this->confirmingDeactivate = true;
    }

    public function cancelDeactivate(): void
    {
        $this->confirmingDeactivate = false;
        $this->reason = '';
    }

    public function deactivate(DeactivateBiodata $deactivate): void
    {
        $this->throttleVisibility();

        $deactivate(auth()->user(), $this->reason);

        session()->flash('status', __('visibility.flash.deactivated'));
        $this->redirectRoute('my-biodata');
    }

    public function reactivate(ReactivateBiodata $reactivate): void
    {
        $this->throttleVisibility();

        $status = $reactivate(auth()->user());

        session()->flash('status', $status === BiodataStatus::Approved
            ? __('visibility.flash.reactivated')
            : __('visibility.flash.sent_for_review'));
        $this->redirectRoute('my-biodata');
    }

    public function render(BiodataProgress $progress)
    {
        $biodata = $this->current();
        $status = $biodata?->status;

        $steps = [];
        $firstIncomplete = null;

        foreach (BiodataProgress::ALL_STEPS as $index => $step) {
            $done = $progress->isStepComplete($biodata, $step);
            $steps[] = ['label' => __('biodata.steps.'.$step), 'done' => $done];

            if (! $done && $firstIncomplete === null) {
                $firstIncomplete = $index + 1;
            }
        }

        return view('livewire.biodata.my-biodata', [
            'biodata' => $biodata,
            'status' => $status,
            'steps' => $steps,
            'percent' => $biodata?->completion_pct ?? 0,
            'canEdit' => $biodata !== null
                ? Gate::allows('update', $biodata)
                : Gate::allows('create', Biodata::class),
            'canSubmit' => $biodata !== null && $firstIncomplete === null && Gate::allows('submit', $biodata),
            'allDone' => $firstIncomplete === null,
            'isDeactivated' => $status === BiodataStatus::Deactivated,
            'canManagePhotos' => $biodata !== null && Gate::allows('managePhotos', $biodata),
            'canHide' => $biodata !== null && Gate::allows('hide', $biodata),
            'canUnhide' => $biodata !== null && Gate::allows('unhide', $biodata),
            'hiddenByStaff' => $status === BiodataStatus::Hidden && $biodata?->hidden_source !== HiddenSource::Owner,
            'canDeactivate' => $biodata !== null && Gate::allows('deactivate', $biodata),
            'canReactivate' => $biodata !== null && Gate::allows('reactivate', $biodata),
            'reasons' => DeactivationReason::cases(),
            'unhideNeedsReview' => in_array($status, [BiodataStatus::Hidden, BiodataStatus::Deactivated], true)
                && ($biodata?->hidden_from !== BiodataStatus::Approved->value
                    || (int) $biodata?->hidden_revision !== (int) $biodata?->revision),
        ])->title(__('overview.title'));
    }

    /** At most 5 hide / show actions per hour per member. */
    private function throttleVisibility(): void
    {
        $key = 'biodata-visibility:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function current(): ?Biodata
    {
        return Biodata::query()
            ->where('user_id', auth()->id())
            ->with(['educations', 'preference', 'guardian'])
            ->first();
    }
}