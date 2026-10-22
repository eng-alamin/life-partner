<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Actions\Account\CancelAccountDeletion;
use App\Actions\Account\RequestAccountDeletion;
use App\Support\Digits;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The member's own "delete my account" page. It only ever works on the signed-in user.
 */
#[Layout('layouts::app')]
class DeleteAccount extends Component
{
    /** Untrusted. The password, or the account email for accounts without a password. Checked inside the action, never stored or logged. */
    public string $proof = '';

    public bool $confirming = false;

    public function startConfirm(): void
    {
        $this->confirming = true;
    }

    public function stopConfirm(): void
    {
        $this->confirming = false;
        $this->proof = '';
    }

    public function requestDeletion(RequestAccountDeletion $request): void
    {
        $this->throttle('account-deletion', 5);

        $request(auth()->user(), $this->proof);

        $this->proof = '';

        session()->flash('status', __('account_deletion.flash.requested'));
        $this->redirectRoute('account.delete');
    }

    public function cancelDeletion(CancelAccountDeletion $cancel): void
    {
        $this->throttle('account-deletion-cancel', 10);

        $cancel(auth()->user());

        session()->flash('status', __('account_deletion.flash.cancelled'));
        $this->redirectRoute('my-biodata');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.account.delete-account', [
            'requested' => $user->hasDeletionRequested(),
            'scheduledOn' => $user->deletion_scheduled_for !== null
                ? Digits::localize($user->deletion_scheduled_for->translatedFormat('j M Y'))
                : null,
            'usesPassword' => filled($user->password),
            'isStaff' => $user->roles()->exists(),
            'waitDays' => Digits::localize(RequestAccountDeletion::WAIT_DAYS),
        ])->title(__('account_deletion.title'));
    }

    /** Limits attempts per hour per member (this also slows down password guessing). */
    private function throttle(string $name, int $max): void
    {
        $key = $name.':'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
