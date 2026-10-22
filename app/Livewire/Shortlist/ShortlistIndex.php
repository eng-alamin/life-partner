<?php

declare(strict_types=1);

namespace App\Livewire\Shortlist;

use App\Actions\Lists\ManageIgnoreList;
use App\Actions\Lists\ManageShortlist;
use App\Models\Biodata;
use App\Models\IgnoredBiodata;
use App\Models\Shortlist;
use App\Models\User;
use App\Services\Block\BlockGuard;
use App\Services\Search\BiodataSearch;
use App\Services\Search\BiodataVisibility;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The member's own shortlist and ignore list. Only biodatas the member may
 * still open are listed: approved, of the opposite type, not blocked. Others
 * stay in the list but are not shown. Removing works on the caller's own rows only.
 */
#[Layout('layouts::app')]
class ShortlistIndex extends Component
{
    private const MAX_ROWS = 200;

    private const TABS = ['shortlist', 'ignored'];

    #[Url(except: 'shortlist')]
    public string $tab = 'shortlist';

    public string $notice = '';

    public function mount(): void
    {
        $this->tab = in_array($this->tab, self::TABS, true) ? $this->tab : 'shortlist';
    }

    public function showTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'shortlist';
        $this->notice = '';
        $this->resetErrorBag();
    }

    public function remove(ManageShortlist $lists, string $code): void
    {
        $this->begin();
        $lists->remove(auth()->user(), $code);
        $this->notice = __('lists.ui.removed_from_shortlist');
    }

    public function restore(ManageIgnoreList $lists, string $code): void
    {
        $this->begin();
        $lists->restore(auth()->user(), $code);
        $this->notice = __('lists.ui.restored');
    }

    public function render(BiodataSearch $search, BiodataVisibility $visibility, BlockGuard $blocks)
    {
        $member = $visibility->memberOrNull(auth()->user());

        abort_if($member === null, 403);

        $model = $this->tab === 'ignored' ? IgnoredBiodata::class : Shortlist::class;
        $paused = $visibility->isPaused($member);

        return view('livewire.shortlist.shortlist-index', [
            'paused' => $paused,
            // A paused member sees no cards (no query is run).
            'cards' => $paused ? [] : $this->cards($member, $model, $search, $visibility, $blocks),
        ])->title(__('lists.ui.title'));
    }

    /**
     * @param  class-string<Shortlist|IgnoredBiodata>  $model
     * @return list<array<string, mixed>>
     */
    private function cards(User $member, string $model, BiodataSearch $search, BiodataVisibility $visibility, BlockGuard $blocks): array
    {
        $type = $search->ownType($member)?->opposite();

        if ($type === null) {
            return [];
        }

        $ids = $model::query()
            ->where('user_id', $member->getKey())
            ->orderByDesc('id')
            ->limit(self::MAX_ROWS)
            ->pluck('biodata_id');

        $biodatas = $blocks->excludeFor(
            Biodata::query()
                ->searchable()
                ->where('type', $type->value)
                ->whereIn('id', $ids)
                ->with(['presentDistrict', 'mainPhoto']),
            (int) $member->getKey(),
        )->get()->keyBy('id');

        $cards = [];

        foreach ($ids as $id) {
            $biodata = $biodatas->get($id);

            if ($biodata === null || (int) $biodata->user_id === (int) $member->getKey()) {
                continue;
            }

            $cards[] = $visibility->card($biodata) + ['photo_url' => $visibility->cardPhotoUrl($member, $biodata)];
        }

        return $cards;
    }

    private function begin(): void
    {
        $this->notice = '';
        $this->resetErrorBag();

        $key = 'list-action:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 120)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
