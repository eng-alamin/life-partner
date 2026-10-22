<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StaffPermission;
use App\Services\Admin\ActivityLogPresenter;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only list of the activity log, newest first, with a filter by log name.
 *
 * It shows codes, statuses and numbers only (see ActivityLogPresenter): no name, email,
 * phone, biodata content or free text. Only an admin with users.manage can open it.
 * It has no action that changes anything.
 */
#[Layout('layouts::admin')]
class ActivityLogList extends Component
{
    use WithPagination;

    private const PER_PAGE = 30;

    /** Empty means "all". Any other value is checked against the fixed list on every render. */
    #[Url(except: '')]
    public string $log = '';

    public function mount(): void
    {
        Gate::authorize(StaffPermission::ManageUsers->value);

        $this->log = $this->cleanLog();
    }

    public function updatedLog(): void
    {
        $this->log = $this->cleanLog();
        $this->resetPage();
    }

    public function render(ActivityLogPresenter $presenter)
    {
        Gate::authorize(StaffPermission::ManageUsers->value);

        $log = $this->cleanLog();

        $activities = Activity::query()
            ->select(['id', 'log_name', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'created_at'])
            ->when($log !== '', fn ($query) => $query->where('log_name', $log))
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE);

        return view('livewire.admin.activity-log-list', [
            'activities' => $activities,
            'rows' => $presenter->rows($activities->getCollection()),
            'logs' => ActivityLogPresenter::LOGS,
        ])->title(__('admin_activity.title'));
    }

    private function cleanLog(): string
    {
        return in_array($this->log, ActivityLogPresenter::LOGS, true) ? $this->log : '';
    }
}
