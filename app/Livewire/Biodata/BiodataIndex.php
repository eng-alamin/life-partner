<?php

declare(strict_types=1);

namespace App\Livewire\Biodata;

use App\Actions\Lists\ManageIgnoreList;
use App\Actions\Lists\ManageShortlist;
use App\Actions\Search\SaveSearch;
use App\Enums\BiodataType;
use App\Enums\EducationLevel;
use App\Enums\MaritalStatus;
use App\Enums\PracticingLevel;
use App\Models\Biodata;
use App\Models\District;
use App\Models\SavedSearch;
use App\Models\Shortlist;
use App\Models\User;
use App\Services\Search\BiodataSearch;
use App\Services\Search\BiodataVisibility;
use App\Services\Search\SearchCriteria;
use App\Support\Countries;
use App\Support\Digits;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Search page for guests (cards only, few filters, 3 pages) and members (all
 * filters, opposite type, own biodata hidden). Every filter is untrusted: it is
 * validated by SearchCriteria, and the guest limits are applied on the server.
 */
#[Layout('layouts::browse')]
class BiodataIndex extends Component
{
    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $age_min = '';

    #[Url(except: '')]
    public string $age_max = '';

    #[Url(except: '')]
    public string $height_min = '';

    #[Url(except: '')]
    public string $height_max = '';

    #[Url(except: '')]
    public string $marital_status = '';

    #[Url(except: '')]
    public string $country = '';

    #[Url(except: '')]
    public string $district_id = '';

    #[Url(except: '')]
    public string $min_education = '';

    #[Url(except: '')]
    public string $profession = '';

    #[Url(except: '')]
    public string $practicing_level = '';

    #[Url(except: 1)]
    public int $page = 1;

    public string $saveName = '';

    public string $listNotice = '';

    public function mount(): void
    {
        // 60 searches a minute per member (or per IP for guests).
        abort_if(RateLimiter::tooManyAttempts($this->throttleKey(), 60), 429);

        RateLimiter::hit($this->throttleKey(), 60);
    }

    /** Applies the filters typed in the form and goes back to the first page. */
    public function search(): void
    {
        $this->throttle();
        $this->normalize();

        $this->validate(SearchCriteria::rules(), [], $this->attributeNames());

        $this->page = 1;
    }

    public function clear(): void
    {
        $this->throttle();
        $this->resetErrorBag();
        $this->fillFilters([]);
        $this->page = 1;
    }

    public function previous(): void
    {
        $this->throttle();
        $this->page = max(1, $this->page - 1);
    }

    public function next(): void
    {
        $this->throttle();
        $this->page = min(BiodataSearch::MAX_PAGE, $this->page + 1);
    }

    public function saveSearch(SaveSearch $save, BiodataVisibility $visibility): void
    {
        $member = $this->memberOrFail($visibility);

        $this->throttle();
        $this->normalize();
        $this->validate(SearchCriteria::rules(), [], $this->attributeNames());

        $save($member, $this->saveName, $this->criteria());

        $this->saveName = '';
    }

    /** Adds a card to the shortlist, or removes it if it is already there. */
    public function toggleShortlist(ManageShortlist $lists, BiodataVisibility $visibility, string $code): void
    {
        $member = $this->memberOrFail($visibility);

        $this->beginListAction();

        $onList = Shortlist::query()
            ->where('user_id', $member->getKey())
            ->whereIn('biodata_id', Biodata::query()->where('public_code', $code)->select('id'))
            ->exists();

        if ($onList) {
            $lists->remove($member, $code);
            $this->listNotice = __('lists.ui.removed_from_shortlist');

            return;
        }

        $lists->add($member, $code);
        $this->listNotice = __('lists.ui.added_to_shortlist');
    }

    /** Hides a card from this member's own search. */
    public function ignore(ManageIgnoreList $lists, BiodataVisibility $visibility, string $code): void
    {
        $member = $this->memberOrFail($visibility);

        $this->beginListAction();

        $lists->ignore($member, $code);
        $this->listNotice = __('lists.ui.ignored');
    }

    public function applySaved(int $id, BiodataVisibility $visibility): void
    {
        $member = $this->memberOrFail($visibility);

        $this->throttle();

        // Scoped to the signed-in member: another member's id finds nothing.
        $saved = SavedSearch::query()->where('user_id', $member->getKey())->findOrFail($id);

        // Stored filters are re-validated; anything that no longer passes is dropped.
        $this->fillFilters(SearchCriteria::lenient((array) $saved->filters)->toArray(forStorage: true));
        $this->resetErrorBag();
        $this->page = 1;
    }

    public function deleteSaved(int $id, BiodataVisibility $visibility): void
    {
        $member = $this->memberOrFail($visibility);

        SavedSearch::query()->where('user_id', $member->getKey())->whereKey($id)->delete();
    }

    public function render(BiodataSearch $search, BiodataVisibility $visibility)
    {
        $member = $visibility->memberOrNull(auth()->user());
        $criteria = $this->criteria();

        $paused = $member !== null && $visibility->isPaused($member);

        // A paused member gets no results at all (no query is run).
        $type = $paused ? null : $search->typeFor($member, $criteria);
        $results = $paused ? null : $search->search($criteria, $member, $this->page);

        // A page past the end (an edited URL, or a saved search with fewer results) starts over.
        if ($results !== null && $this->page > 1 && $results->items() === []) {
            $this->page = 1;
            $results = $search->search($criteria, $member, 1);
        }

        $cards = $results === null
            ? []
            : collect($results->items())->map(fn ($biodata): array => $visibility->card($biodata) + ['photo_url' => $visibility->cardPhotoUrl($member, $biodata)])->all();

        $currentPage = $results?->currentPage() ?? 1;
        $this->page = $currentPage; // keeps the URL and the buttons in step with the page shown
        $guestLimitReached = $member === null && $currentPage >= BiodataSearch::GUEST_MAX_PAGES && $results?->hasMorePages();

        return view('livewire.biodata.biodata-index', [
            'isMember' => $member !== null,
            'cards' => $cards,
            'shortlistedCodes' => $member === null ? [] : $this->shortlistedCodes($member, $cards),
            'blocked' => $paused ? 'paused' : ($results === null ? ($member === null ? 'choose_type' : 'need_biodata') : null),
            'searchType' => $type,
            'currentPage' => $currentPage,
            'hasPrevious' => $currentPage > 1,
            'hasNext' => $results !== null && $results->hasMorePages() && ! $guestLimitReached,
            'guestLimitReached' => (bool) $guestLimitReached,
            'savedSearches' => $member === null ? collect() : $this->savedSearchesFor($member),
            'options' => [
                'type' => BiodataType::cases(),
                'marital_status' => MaritalStatus::options(),
                'min_education' => EducationLevel::options(),
                'practicing_level' => PracticingLevel::options(),
                'countries' => Countries::options(),
            ],
            'districts' => District::query()->orderBy('name_en')->get(),
        ])->title(__('search.title'));
    }

    /** Filters from the page properties. Anything invalid is ignored here (see search() for errors). */
    private function criteria(): SearchCriteria
    {
        return SearchCriteria::lenient($this->filterValues());
    }

    /** @return array<string, string> */
    private function filterValues(): array
    {
        $values = [];

        foreach (SearchCriteria::KEYS as $key) {
            $values[$key] = (string) $this->{$key};
        }

        return $values;
    }

    /** @param array<string, string> $values */
    private function fillFilters(array $values): void
    {
        foreach (SearchCriteria::KEYS as $key) {
            // A member's type always comes from their own biodata, never from a saved search.
            if ($key === 'type' && $values !== []) {
                continue;
            }

            $this->{$key} = (string) ($values[$key] ?? '');
        }
    }

    /** Trims text and turns Bangla digits into ASCII digits before validating. */
    private function normalize(): void
    {
        foreach (['age_min', 'age_max', 'height_min', 'height_max', 'district_id'] as $field) {
            $this->{$field} = trim((string) Digits::normalize($this->{$field}));
        }

        $this->profession = trim((string) preg_replace('/\s+/u', ' ', $this->profession));
    }

    /** @return Collection<int, SavedSearch> */
    private function savedSearchesFor(User $member)
    {
        return SavedSearch::query()
            ->where('user_id', $member->getKey())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Which of the shown cards are on the member's shortlist (one query).
     *
     * @param  list<array<string, mixed>>  $cards
     * @return list<string>
     */
    private function shortlistedCodes(User $member, array $cards): array
    {
        if ($cards === []) {
            return [];
        }

        return Biodata::query()
            ->whereIn('public_code', array_column($cards, 'code'))
            ->whereIn('id', Shortlist::query()->where('user_id', $member->getKey())->select('biodata_id'))
            ->pluck('public_code')
            ->all();
    }

    private function beginListAction(): void
    {
        $this->listNotice = '';
        $this->resetErrorBag();

        $key = 'list-action:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 120)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function memberOrFail(BiodataVisibility $visibility): User
    {
        $member = $visibility->memberOrNull(auth()->user());

        abort_if($member === null || $visibility->isPaused($member), 403);

        return $member;
    }

    private function throttleKey(): string
    {
        return 'biodata-search:'.(auth()->id() ?? request()->ip());
    }

    private function throttle(): void
    {
        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    /** @return array<string, string> */
    private function attributeNames(): array
    {
        return [
            'type' => __('search.fields.type'),
            'age_min' => __('search.fields.age_min'),
            'age_max' => __('search.fields.age_max'),
            'height_min' => __('search.fields.height_min'),
            'height_max' => __('search.fields.height_max'),
            'marital_status' => __('search.fields.marital_status'),
            'country' => __('search.fields.country'),
            'district_id' => __('search.fields.district'),
            'min_education' => __('search.fields.min_education'),
            'profession' => __('search.fields.profession'),
            'practicing_level' => __('search.fields.practicing_level'),
        ];
    }
}
