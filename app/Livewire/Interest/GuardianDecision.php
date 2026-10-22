<?php

declare(strict_types=1);

namespace App\Livewire\Interest;

use App\Actions\Interest\DecideGuardianApproval;
use App\Services\Interest\GuardianLinks;
use App\Services\Search\BiodataVisibility;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Public page opened from the guardian's one-time link (no login). It shows the
 * public card of the member whose interest was accepted and lets the guardian
 * approve or decline once. The token cannot be changed from the browser, and
 * every rule is checked again by DecideGuardianApproval.
 */
#[Layout('layouts::auth')]
class GuardianDecision extends Component
{
    private const ATTEMPTS_PER_HOUR = 20;

    #[Locked]
    public string $token = '';

    /** '' while open, then 'approved' or 'declined'. */
    public string $outcome = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function approve(DecideGuardianApproval $decide): void
    {
        $this->decide($decide, true);
    }

    public function decline(DecideGuardianApproval $decide): void
    {
        $this->decide($decide, false);
    }

    public function render(GuardianLinks $links, BiodataVisibility $visibility)
    {
        $card = null;
        $open = false;

        if ($this->outcome === '') {
            $interest = $links->findOpen($this->token);

            if ($interest !== null && $interest->sender !== null) {
                $open = true;
                $card = $visibility->card($interest->sender);
            }
        }

        return view('livewire.interest.guardian-decision', [
            'open' => $open,
            'card' => $card,
        ])->title(__('guardian.page.title'));
    }

    private function decide(DecideGuardianApproval $decide, bool $approve): void
    {
        $this->resetErrorBag();

        $key = 'guardian-decide:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::ATTEMPTS_PER_HOUR)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);

        $decide($this->token, $approve);

        $this->outcome = $approve ? 'approved' : 'declined';
    }
}
