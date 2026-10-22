<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Reports for staff. "Open" lists the oldest first; "closed" lists the most
 * recently handled first. The list shows the code, reason and age only: the
 * reporter's text is read on the report page.
 */
#[Layout('layouts::admin')]
class ReportQueue extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    private const TABS = ['open', 'closed'];

    #[Url(as: 'tab')]
    public string $tab = 'open';

    public function mount(): void
    {
        Gate::authorize('viewAny', Report::class);

        $this->normalizeTab();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('viewAny', Report::class);

        $this->tab = $tab;
        $this->normalizeTab();
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('viewAny', Report::class);

        $this->normalizeTab();

        $query = Report::query()->select(['id', 'code', 'reported_code', 'reason', 'status', 'created_at', 'handled_at']);

        if ($this->tab === 'open') {
            $query->where('status', ReportStatus::Open->value)->orderBy('created_at')->orderBy('id');
        } else {
            $query->where('status', '!=', ReportStatus::Open->value)->orderByDesc('handled_at')->orderByDesc('id');
        }

        return view('livewire.admin.report-queue', [
            'items' => $query->simplePaginate(self::PER_PAGE),
        ])->title(__('admin_reports.queue.title'));
    }

    private function normalizeTab(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'open';
        }
    }
}
