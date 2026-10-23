<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StoryStatus;
use App\Models\SuccessStory;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * All success stories for staff with stories.manage, most recently changed first.
 * The list shows the label, the title, the status and the consent state, never the text.
 */
#[Layout('layouts::admin')]
class StoryList extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    private const TABS = ['all', 'draft', 'published'];

    #[Url(as: 'tab')]
    public string $tab = 'all';

    public function mount(): void
    {
        Gate::authorize('viewAny', SuccessStory::class);

        $this->normalizeTab();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('viewAny', SuccessStory::class);

        $this->tab = $tab;
        $this->normalizeTab();
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('viewAny', SuccessStory::class);

        $this->normalizeTab();

        $query = SuccessStory::query()
            ->select([
                'id', 'code', 'couple_label', 'title_bn', 'title_en', 'status',
                'consent_given_by', 'consent_form', 'consent_given_at', 'consent_withdrawn_at', 'updated_at',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($this->tab === 'draft') {
            $query->where('status', StoryStatus::Draft->value);
        } elseif ($this->tab === 'published') {
            $query->where('status', StoryStatus::Published->value);
        }

        $items = $query->simplePaginate(self::PER_PAGE);

        $rows = $items->getCollection()->map(fn (SuccessStory $story): array => [
            'code' => $story->code,
            'label' => $story->couple_label,
            'title' => $story->title(),
            'status' => $story->status,
            'consent' => $story->consentState(),
            'updated' => $story->updated_at?->diffForHumans(),
        ])->all();

        return view('livewire.admin.story-list', [
            'items' => $items,
            'rows' => $rows,
        ])->title(__('admin_stories.list.title'));
    }

    private function normalizeTab(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'all';
        }
    }
}
