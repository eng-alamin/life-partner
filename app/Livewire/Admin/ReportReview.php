<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Report\HandleReport;
use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One report. The report id is locked (the browser cannot change it); every
 * decision is checked again on the server inside HandleReport.
 */
#[Layout('layouts::admin')]
class ReportReview extends Component
{
    #[Locked]
    public int $reportId = 0;

    public string $resolution = '';

    public function mount(Report $report): void
    {
        Gate::authorize('handle', $report);

        $this->reportId = (int) $report->getKey();
    }

    public function dismiss(HandleReport $handler): void
    {
        $this->begin();

        $handler->dismiss(auth()->user(), $this->reportId, $this->resolution);

        $this->finish('dismissed');
    }

    public function resolve(HandleReport $handler): void
    {
        $this->begin();

        $handler->resolve(auth()->user(), $this->reportId, $this->resolution);

        $this->finish('resolved');
    }

    public function hideBiodata(HandleReport $handler): void
    {
        $this->begin();

        $handler->hideBiodata(auth()->user(), $this->reportId, $this->resolution);

        $this->finish('hidden');
    }

    public function render()
    {
        $report = Report::query()->with(['reporter.biodata', 'biodata'])->findOrFail($this->reportId);

        Gate::authorize('handle', $report);

        return view('livewire.admin.report-review', [
            'report' => $report,
            'isOpen' => $report->status === ReportStatus::Open,
            'reporterCode' => $report->reporter?->biodata?->public_code,
            'counts' => $this->counts($report),
        ])->title(__('admin_reports.review.title'));
    }

    /** @return array{total: int, open: int} reports about the same biodata, this one included */
    private function counts(Report $report): array
    {
        if ($report->biodata_id === null) {
            return ['total' => 0, 'open' => 0];
        }

        $row = Report::query()
            ->where('biodata_id', $report->biodata_id)
            ->selectRaw('count(*) as total, sum(case when status = ? then 1 else 0 end) as open_total', [ReportStatus::Open->value])
            ->first();

        return ['total' => (int) $row?->total, 'open' => (int) $row?->open_total];
    }

    private function begin(): void
    {
        $this->resetErrorBag();

        $key = 'report-review:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function finish(string $outcome): void
    {
        session()->flash('status', __('admin_reports.flash.'.$outcome));
        $this->redirectRoute('admin.reports');
    }
}
