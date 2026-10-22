<?php

declare(strict_types=1);

namespace App\Livewire\Interest;

use App\Actions\Interest\SendInterest;
use App\Models\Biodata;
use App\Services\Interest\InterestRelation;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The interest panel on a profile page. The receiver is identified by the
 * public code only. The browser may change the message, never the code
 * (it is locked), and SendInterest checks every rule again on the server.
 */
class SendInterestButton extends Component
{
    private const HOURLY_LIMIT = 30;

    #[Locked]
    public string $code = '';

    public string $message = '';

    public string $notice = '';

    public function mount(string $code): void
    {
        $this->code = $code;
    }

    public function send(SendInterest $send): void
    {
        $this->notice = '';
        $this->resetErrorBag();
        $this->throttle();

        $send(auth()->user(), $this->code, $this->message);

        $this->reset('message');
        $this->notice = __('interests.ui.sent');
    }

    public function render(InterestRelation $relation)
    {
        $target = Biodata::query()->searchable()->where('public_code', $this->code)->first();

        return view('livewire.interest.send-interest-button', [
            'state' => $target === null ? null : $relation->stateFor(auth()->user(), $target)->value,
            'max' => (int) config('lifepartner.interests.message_max'),
        ]);
    }

    private function throttle(): void
    {
        $key = 'interest-send:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, self::HOURLY_LIMIT)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
