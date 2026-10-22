<?php

declare(strict_types=1);

namespace App\Livewire\Block;

use App\Actions\Block\BlockMember;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Block this member" on a profile page. The member is identified by the
 * public code only (locked, so the browser cannot change it) and BlockMember
 * checks every rule again on the server.
 */
class BlockButton extends Component
{
    private const HOURLY_LIMIT = 30;

    #[Locked]
    public string $code = '';

    public function mount(string $code): void
    {
        $this->code = $code;
    }

    public function block(BlockMember $block): void
    {
        $this->resetErrorBag();
        $this->throttle();

        $block(auth()->user(), $this->code);

        session()->flash('blocks.notice', __('blocks.ui.blocked'));

        $this->redirect(route('blocks.index'));
    }

    public function render()
    {
        return view('livewire.block.block-button');
    }

    private function throttle(): void
    {
        $key = 'block-action:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, self::HOURLY_LIMIT)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
