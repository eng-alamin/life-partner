<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Identity\ReviewIdentityVerification;
use App\Enums\IdentityRejectionReason;
use App\Models\IdentityVerification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One identity request for staff. The code is locked (the browser cannot change it); every
 * decision is checked again on the server inside the action, which also locks the row.
 * The pictures are loaded from a separate authorized route, never from this component.
 */
#[Layout('layouts::admin')]
class IdentityReview extends Component
{
    #[Locked]
    public string $verificationCode = '';

    /** Untrusted. One value of IdentityRejectionReason, checked in the action. */
    public string $reason = '';

    public function mount(IdentityVerification $verification): void
    {
        Gate::authorize('review', $verification);

        $this->verificationCode = $verification->code;
    }

    public function approve(ReviewIdentityVerification $review): void
    {
        $this->begin();

        $review->approve(auth()->user(), $this->verificationCode);

        $this->finish('approved');
    }

    public function reject(ReviewIdentityVerification $review): void
    {
        $this->begin();

        $review->reject(auth()->user(), $this->verificationCode, $this->reason);

        $this->finish('rejected');
    }

    public function render()
    {
        $verification = IdentityVerification::query()
            ->with('user.biodata:id,user_id,public_code,identity_verified_at')
            ->where('code', $this->verificationCode)
            ->firstOrFail();

        Gate::authorize('review', $verification);

        $canSeeFiles = Gate::allows('viewFile', $verification);

        return view('livewire.admin.identity-review', [
            'verification' => $verification,
            'memberCode' => $verification->user?->biodata?->public_code,
            'canDecide' => $verification->isPending(),
            'hasFront' => $canSeeFiles && $verification->front_path !== null,
            'hasBack' => $canSeeFiles && $verification->back_path !== null,
            'reasons' => IdentityRejectionReason::forStaff(),
        ])->title(__('admin_identity.review.title'));
    }

    private function begin(): void
    {
        $this->resetErrorBag();

        $key = 'identity-review:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function finish(string $outcome): void
    {
        session()->flash('status', __('admin_identity.flash.'.$outcome));
        $this->redirectRoute('admin.verification');
    }
}
