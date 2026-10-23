<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\IdentityStatus;
use App\Models\IdentityVerification;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Identity requests for staff. "Review" lists requests waiting for a decision, oldest first
 * (the staff member's own request is never listed); "decided" lists approved and rejected
 * requests, newest first. The list shows only the member's biodata code, the document type
 * and the status. No picture and no personal detail is shown here.
 */
#[Layout('layouts::admin')]
class IdentityQueue extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    private const TABS = ['review', 'decided'];

    #[Url(as: 'tab')]
    public string $tab = 'review';

    public function mount(): void
    {
        Gate::authorize('viewAny', IdentityVerification::class);

        $this->normalizeTab();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('viewAny', IdentityVerification::class);

        $this->tab = $tab;
        $this->normalizeTab();
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('viewAny', IdentityVerification::class);

        $this->normalizeTab();

        $query = IdentityVerification::query()
            ->select(['id', 'code', 'user_id', 'status', 'document_type', 'submitted_at', 'reviewed_at'])
            ->with('user.biodata:id,user_id,public_code');

        if ($this->tab === 'review') {
            $query->where('status', IdentityStatus::Pending->value)
                ->where('user_id', '!=', auth()->id())
                ->orderBy('submitted_at')
                ->orderBy('id');
        } else {
            $query->whereIn('status', [IdentityStatus::Approved->value, IdentityStatus::Rejected->value])
                ->orderByDesc('reviewed_at')
                ->orderByDesc('id');
        }

        $items = $query->simplePaginate(self::PER_PAGE);

        $rows = $items->getCollection()->map(fn (IdentityVerification $item): array => [
            'code' => $item->code,
            'member' => $item->user?->biodata?->public_code,
            'document' => $item->document_type,
            'status' => $item->status,
            'when' => ($this->tab === 'review' ? $item->submitted_at : $item->reviewed_at)?->diffForHumans(),
        ])->all();

        return view('livewire.admin.identity-queue', [
            'items' => $items,
            'rows' => $rows,
        ])->title(__('admin_identity.queue.title'));
    }

    private function normalizeTab(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'review';
        }
    }
}
