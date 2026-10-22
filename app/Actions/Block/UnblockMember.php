<?php

declare(strict_types=1);

namespace App\Actions\Block;

use App\Models\Biodata;
use App\Models\Block;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Removes the signed-in member's own block of the member behind a public code.
 * Only rows where the caller is the blocker are touched, so nobody can remove
 * someone else's block. Cancelled interests are not brought back.
 */
final class UnblockMember
{
    public function __invoke(User $user, string $code): void
    {
        DB::transaction(function () use ($user, $code): void {
            $target = Biodata::withTrashed()->where('public_code', $code)->first();

            if ($target === null) {
                throw ValidationException::withMessages(['form' => __('blocks.errors.unavailable')]);
            }

            $removed = Block::query()
                ->where('blocker_user_id', $user->getKey())
                ->where('blocked_user_id', $target->user_id)
                ->delete();

            if ($removed > 0) {
                activity('block')
                    ->causedBy($user)
                    ->withProperties(['target_code' => $target->public_code])
                    ->log('block.removed');
            }
        });
    }
}
