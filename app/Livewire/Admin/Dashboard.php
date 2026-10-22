<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\BiodataStatus;
use App\Enums\ReportStatus;
use App\Enums\StaffPermission;
use App\Models\Biodata;
use App\Models\Report;
use App\Models\User;
use App\Services\Admin\WaitingCounts;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin')]
class Dashboard extends Component
{
    public function mount(): void
    {
        Gate::authorize(StaffPermission::AccessAdmin->value);
    }

    public function render(WaitingCounts $waitingCounts)
    {
        /** @var array<string, int> $byStatus */
        $byStatus = Biodata::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $canHandleReports = auth()->user()?->can('viewAny', Report::class) === true;

        return view('livewire.admin.dashboard', [
            'pending' => $byStatus[BiodataStatus::Pending->value] ?? 0,
            'approved' => $byStatus[BiodataStatus::Approved->value] ?? 0,
            'rejected' => $byStatus[BiodataStatus::Rejected->value] ?? 0,
            'drafts' => $byStatus[BiodataStatus::Draft->value] ?? 0,
            'members' => User::query()->count(),
            'waiting' => auth()->user() !== null ? $waitingCounts->for(auth()->user()) : [],
            'waitingRoutes' => WaitingCounts::ROUTES,
            'canHandleReports' => $canHandleReports,
            'openReports' => $canHandleReports
                ? Report::query()->where('status', ReportStatus::Open->value)->count()
                : 0,
        ])->title(__('admin.dashboard.title'));
    }
}
