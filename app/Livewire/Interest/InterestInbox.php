<?php

declare(strict_types=1);

namespace App\Livewire\Interest;

use App\Actions\Interest\CancelInterest;
use App\Actions\Interest\IssueGuardianLink;
use App\Actions\Interest\RespondToInterest;
use App\Actions\Interest\RevealContact;
use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Services\Block\BlockGuard;
use App\Services\Interest\ContactReveal;
use App\Services\Search\BiodataVisibility;
use App\Support\Digits;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The signed-in member's interests: received and sent. It always works on the
 * member's own biodata. Codes sent from the browser are looked up only inside
 * that member's own interests by the actions, and no model is kept in a public
 * property, so nothing private is sent to the browser.
 */
#[Layout('layouts::app')]
class InterestInbox extends Component
{
    private const PAGE_SIZE = 20;

    private const MAX_ROWS = 200;

    private const TABS = ['received', 'sent'];

    #[Url(except: 'received')]
    public string $tab = 'received';

    public int $limit = self::PAGE_SIZE;

    public string $notice = '';

    /** The freshly created guardian link, shown once. Cannot be set from the browser. */
    #[Locked]
    public string $guardianLink = '';

    /** Code of the interest the shown link belongs to. */
    #[Locked]
    public string $guardianLinkCode = '';

    /** Code of the interest whose guardian contact is shown. Only the code is kept, never the details. */
    #[Locked]
    public string $contactCode = '';

    public function mount(): void
    {
        $this->tab = in_array($this->tab, self::TABS, true) ? $this->tab : 'received';
    }

    public function showTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'received';
        $this->limit = self::PAGE_SIZE;
        $this->notice = '';
        $this->closeLink();
        $this->closeContact();
        $this->resetErrorBag();
    }

    public function loadMore(): void
    {
        $this->limit = min($this->limit + self::PAGE_SIZE, self::MAX_ROWS);
    }

    public function accept(RespondToInterest $respond, string $code): void
    {
        $this->answer($respond, $code, InterestStatus::Accepted, 'accepted');
    }

    public function decline(RespondToInterest $respond, string $code): void
    {
        $this->answer($respond, $code, InterestStatus::Declined, 'declined');
    }

    public function cancel(CancelInterest $cancel, string $code): void
    {
        $this->guarded(function () use ($cancel, $code): void {
            $cancel(auth()->user(), $code);

            $this->notice = __('interests.ui.cancelled');
        });
    }

    public function createGuardianLink(IssueGuardianLink $issue, string $code): void
    {
        $this->guarded(function () use ($issue, $code): void {
            $token = $issue(auth()->user(), $code);

            $this->guardianLink = route('guardian.decide', ['token' => $token]);
            $this->guardianLinkCode = $code;
        });
    }

    public function revealContact(RevealContact $reveal, string $code): void
    {
        $this->guarded(function () use ($reveal, $code): void {
            $reveal(auth()->user(), $code);

            $this->contactCode = $code;
        });
    }

    public function closeContact(): void
    {
        $this->contactCode = '';
    }

    public function closeLink(): void
    {
        $this->guardianLink = '';
        $this->guardianLinkCode = '';
    }

    public function render(BiodataVisibility $visibility, ContactReveal $contacts, BlockGuard $blocks)
    {
        $own = Biodata::query()->where('user_id', auth()->id())->first();

        $rows = [];
        // Members with a block in either direction: shown as "unavailable".
        $hidden = array_flip($blocks->hiddenUserIds((int) auth()->id()));
        $hasMore = false;
        $counts = ['received' => 0, 'sent' => 0];
        // A paused member cannot open other profiles (the page would be a 404), so no link is shown.
        $paused = auth()->user() !== null && $visibility->isPaused(auth()->user());

        if ($own !== null) {
            $received = $this->tab === 'received';

            $interests = Interest::query()
                ->where($received ? 'receiver_biodata_id' : 'sender_biodata_id', $own->getKey())
                ->with($received ? ['sender.presentDistrict', 'sender.guardian'] : ['receiver.presentDistrict', 'receiver.guardian'])
                ->orderByDesc('id')
                ->limit($this->limit + 1)
                ->get();

            $hasMore = $interests->count() > $this->limit;

            foreach ($interests->take($this->limit) as $interest) {
                $rows[] = $this->row($interest, $received, $visibility, $own, $contacts, $hidden, $paused);
            }

            $counts['received'] = $this->openCount('receiver_biodata_id', (int) $own->getKey());
            $counts['sent'] = $this->openCount('sender_biodata_id', (int) $own->getKey());
        }

        return view('livewire.interest.interest-inbox', [
            'hasBiodata' => $own !== null,
            'rows' => $rows,
            'hasMore' => $hasMore && $this->limit < self::MAX_ROWS,
            'counts' => $counts,
        ])->title(__('interests.ui.title'));
    }

    /**
     * One list row as plain values: only the public card of the other member,
     * never employer, income or guardian details (the guardian contact appears only after a reveal).
     *
     * @param  array<int, int>  $hidden  user ids with a block, as array keys
     * @param  bool  $paused  the signed-in member is paused: no profile link
     * @return array<string, mixed>
     */
    private function row(Interest $interest, bool $received, BiodataVisibility $visibility, Biodata $own, ContactReveal $contacts, array $hidden, bool $paused): array
    {
        $other = $received ? $interest->sender : $interest->receiver;
        $blocked = $other !== null && isset($hidden[(int) $other->user_id]);

        // A pending request past its date counts as expired even before the scheduled job runs.
        $status = $interest->status === InterestStatus::Pending && $interest->expires_at?->isPast()
            ? InterestStatus::Expired
            : $interest->status;

        // Contact is decided by ContactReveal; it is rendered only after the member asked for it.
        // A paused member (policy says no) never gets the details, even if a code is still set.
        $allowed = auth()->user()?->can('revealContact', [$interest, $own]) ?? false;
        $details = $allowed ? $contacts->detailsFor($interest, $own) : null;
        $shown = $details !== null && $this->contactCode === $interest->code;

        return [
            'code' => $interest->code,
            'status' => $status,
            'guardian_status' => $interest->guardian_status,
            'message' => $interest->message,
            'sent_on' => Digits::localize($interest->created_at->translatedFormat('j M Y')),
            'expires_on' => $status === InterestStatus::Pending && $interest->expires_at !== null
                ? Digits::localize($interest->expires_at->translatedFormat('j M Y'))
                : null,
            'card' => $other === null || $blocked ? null : $visibility->card($other),
            'profile_url' => ! $paused && $other !== null && ! $blocked && $other->status->isSearchable()
                ? route('biodatas.show', $other->public_code)
                : null,
            'can_respond' => $received && $status === InterestStatus::Pending,
            'can_cancel' => ! $received && $status === InterestStatus::Pending,
            'can_reveal' => $details !== null && ! $shown,
            'contact' => $shown ? $details : null,
            'can_create_link' => $received
                && $status === InterestStatus::Accepted
                && $interest->guardian_status === GuardianApprovalStatus::Pending,
            'link_valid_until' => $interest->guardian_token_hash !== null
                && $interest->guardian_token_expires_at !== null
                && $interest->guardian_token_expires_at->isFuture()
                    ? Digits::localize($interest->guardian_token_expires_at->translatedFormat('j M Y'))
                    : null,
        ];
    }

    /** Pending requests that have not run out of time yet. */
    private function openCount(string $column, int $biodataId): int
    {
        return Interest::query()
            ->where($column, $biodataId)
            ->where('status', InterestStatus::Pending->value)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();
    }

    private function answer(RespondToInterest $respond, string $code, InterestStatus $decision, string $noticeKey): void
    {
        $this->guarded(function () use ($respond, $code, $decision, $noticeKey): void {
            $respond(auth()->user(), $code, $decision);

            $this->notice = __('interests.ui.'.$noticeKey);
        });
    }

    /** Clears old messages, limits the rate, and turns an unknown code into a friendly message. */
    private function guarded(callable $work): void
    {
        $this->notice = '';
        $this->guardianLink = '';
        $this->guardianLinkCode = '';
        $this->contactCode = '';
        $this->resetErrorBag();
        $this->throttle();

        try {
            $work();
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages(['form' => __('interests.errors.not_open')]);
        }
    }

    private function throttle(): void
    {
        $key = 'interest-answer:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
