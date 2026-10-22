<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\BiodataStatus;
use App\Enums\StaffPermission;
use App\Models\Biodata;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** Pending biodatas, oldest first. A reviewer never sees their own biodata here. */
#[Layout('layouts::admin')]
class ApprovalQueue extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    public function mount(): void
    {
        Gate::authorize(StaffPermission::ReviewBiodata->value);
    }

    public function render()
    {
        $items = Biodata::query()
            ->where('status', BiodataStatus::Pending->value)
            ->where('user_id', '!=', auth()->id())
            ->with('presentDistrict')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->simplePaginate(self::PER_PAGE);

        return view('livewire.admin.approval-queue', ['items' => $items])
            ->title(__('admin.queue.title'));
    }
}
