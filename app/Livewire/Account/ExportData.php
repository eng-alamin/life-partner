<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Actions\Account\ConfirmDataExport;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The member's own "download my data" page. It only ever works on the signed-in user.
 */
#[Layout('layouts::app')]
class ExportData extends Component
{
    /** Untrusted. The password, or the account email for accounts without a password. Checked inside the action, never stored or logged. */
    public string $proof = '';

    public function prepare(ConfirmDataExport $confirm): void
    {
        // Clear the message of an earlier wrong try, so it does not stay on the page after a good one.
        $this->resetValidation();

        $this->throttle('account-export-proof', 5);

        $confirm(auth()->user(), $this->proof);

        $this->proof = '';

        $this->redirectRoute('account.export.download');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.account.export-data', [
            'usesPassword' => filled($user->password),
        ])->title(__('account_export.title'));
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
