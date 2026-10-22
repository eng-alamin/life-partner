<?php

declare(strict_types=1);

namespace App\Livewire\Report;

use App\Actions\Report\SubmitReport;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Biodata;
use App\Models\Report;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Report this profile" on a profile page. The biodata is identified by the
 * public code only (locked, so the browser cannot change it) and SubmitReport
 * checks every rule again on the server. The reason and the text are untrusted input.
 */
class ReportButton extends Component
{
    private const HOURLY_LIMIT = 20;

    #[Locked]
    public string $code = '';

    public bool $open = false;

    public string $reason = '';

    public string $details = '';

    public string $notice = '';

    public function mount(string $code): void
    {
        $this->code = $code;
    }

    public function toggle(): void
    {
        $this->resetErrorBag();
        $this->open = ! $this->open;
    }

    public function submit(SubmitReport $report): void
    {
        $this->notice = '';
        $this->resetErrorBag();
        $this->throttle();

        $report(auth()->user(), $this->code, $this->reason, $this->details);

        $this->reset(['open', 'reason', 'details']);
        $this->notice = __('reports.ui.sent');
    }

    public function render()
    {
        return view('livewire.report.report-button', [
            'alreadyReported' => $this->hasOpenReport(),
            'reasons' => ReportReason::cases(),
            'detailsMax' => (int) config('lifepartner.reports.details_max'),
        ]);
    }

    private function hasOpenReport(): bool
    {
        $biodataId = Biodata::query()->where('public_code', $this->code)->value('id');

        if ($biodataId === null) {
            return false;
        }

        return Report::query()
            ->where('reporter_user_id', auth()->id())
            ->where('biodata_id', $biodataId)
            ->where('status', ReportStatus::Open->value)
            ->exists();
    }

    private function throttle(): void
    {
        $key = 'report-action:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, self::HOURLY_LIMIT)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
