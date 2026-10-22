<?php

declare(strict_types=1);

namespace App\Livewire\Shortlist;

use App\Actions\Lists\ManageIgnoreList;
use App\Actions\Lists\ManageShortlist;
use App\Models\Biodata;
use App\Models\IgnoredBiodata;
use App\Models\Shortlist;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Shortlist" and "Ignore" buttons on a profile page. The biodata is identified
 * by the public code only (locked); the actions check every rule on the server.
 */
class ListButtons extends Component
{
    #[Locked]
    public string $code = '';

    public string $notice = '';

    public function mount(string $code): void
    {
        $this->code = $code;
    }

    public function toggleShortlist(ManageShortlist $lists): void
    {
        $this->begin();

        if ($this->state()['shortlisted']) {
            $lists->remove(auth()->user(), $this->code);
            $this->notice = __('lists.ui.removed_from_shortlist');

            return;
        }

        $lists->add(auth()->user(), $this->code);
        $this->notice = __('lists.ui.added_to_shortlist');
    }

    public function toggleIgnore(ManageIgnoreList $lists): void
    {
        $this->begin();

        if ($this->state()['ignored']) {
            $lists->restore(auth()->user(), $this->code);
            $this->notice = __('lists.ui.restored');

            return;
        }

        $lists->ignore(auth()->user(), $this->code);
        $this->notice = __('lists.ui.ignored');
    }

    public function render()
    {
        return view('livewire.shortlist.list-buttons', $this->state());
    }

    /** @return array{shortlisted: bool, ignored: bool} */
    private function state(): array
    {
        $biodataId = Biodata::query()->where('public_code', $this->code)->value('id');

        if ($biodataId === null) {
            return ['shortlisted' => false, 'ignored' => false];
        }

        return [
            'shortlisted' => Shortlist::query()->where('user_id', auth()->id())->where('biodata_id', $biodataId)->exists(),
            'ignored' => IgnoredBiodata::query()->where('user_id', auth()->id())->where('biodata_id', $biodataId)->exists(),
        ];
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
