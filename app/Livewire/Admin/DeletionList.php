<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StaffPermission;
use App\Models\Biodata;
use App\Models\User;
use App\Services\Admin\DeletionPauseReasons;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only list of members who asked to delete their account and are still waiting.
 *
 * Shows the public code, the dates and, when the purge is held back, why. It shows no name,
 * email, phone or biodata content, and it has no action that changes anything.
 * Only an admin with users.manage can open it.
 */
#[Layout('layouts::admin')]
class DeletionList extends Component
{
    use WithPagination;

    private const PER_PAGE = 20;

    public function mount(): void
    {
        Gate::authorize(StaffPermission::ManageUsers->value);
    }

    public function render(DeletionPauseReasons $pauseReasons)
    {
        Gate::authorize(StaffPermission::ManageUsers->value);

        // Staff accounts are never purged, so they are not listed (same rule as the purge).
        $members = User::query()
            ->select(['id', 'deletion_requested_at', 'deletion_scheduled_for'])
            ->whereNotNull('deletion_requested_at')
            ->whereNotNull('deletion_scheduled_for')
            ->whereDoesntHave('roles')
            ->orderBy('deletion_scheduled_for')
            ->orderBy('id')
            ->simplePaginate(self::PER_PAGE);

        $ids = $members->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $codes = $ids === []
            ? collect()
            : Biodata::withTrashed()->whereIn('user_id', $ids)->pluck('public_code', 'user_id');

        return view('livewire.admin.deletion-list', [
            'members' => $members,
            'codes' => $codes,
            'reasons' => $pauseReasons->for($ids),
        ])->title(__('admin_deletions.title'));
    }
}
