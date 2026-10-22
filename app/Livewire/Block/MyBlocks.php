<?php

declare(strict_types=1);

namespace App\Livewire\Block;

use App\Actions\Block\UnblockMember;
use App\Models\Biodata;
use App\Models\Block;
use App\Support\Digits;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The member's own block list. Only public codes and the block date are shown;
 * a blocked profile itself stays hidden. Unblocking works on the caller's own
 * blocks only.
 */
#[Layout('layouts::app')]
class MyBlocks extends Component
{
    private const MAX_ROWS = 200;

    public string $notice = '';

    public function mount(): void
    {
        $this->notice = (string) session('blocks.notice', '');
    }

    public function unblock(UnblockMember $unblock, string $code): void
    {
        $this->notice = '';
        $this->resetErrorBag();
        $this->throttle();

        $unblock(auth()->user(), $code);

        $this->notice = __('blocks.ui.unblocked');
    }

    public function render()
    {
        $blocks = Block::query()
            ->where('blocker_user_id', auth()->id())
            ->orderByDesc('id')
            ->limit(self::MAX_ROWS)
            ->get(['id', 'blocked_user_id', 'created_at']);

        $codes = Biodata::withTrashed()
            ->whereIn('user_id', $blocks->pluck('blocked_user_id'))
            ->pluck('public_code', 'user_id');

        $rows = [];

        foreach ($blocks as $block) {
            $code = $codes[$block->blocked_user_id] ?? null;

            if ($code === null) {
                continue;
            }

            $rows[] = [
                'code' => (string) $code,
                'blocked_on' => Digits::localize($block->created_at->translatedFormat('j M Y')),
            ];
        }

        return view('livewire.block.my-blocks', ['rows' => $rows])->title(__('blocks.ui.title'));
    }

    private function throttle(): void
    {
        $key = 'block-action:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
