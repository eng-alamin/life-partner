<?php

declare(strict_types=1);

namespace App\Actions\Block;

use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Block;
use App\Models\Interest;
use App\Models\User;
use App\Services\Interest\InterestTransitions;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Blocks the owner of an approved biodata, found by its public code only.
 *
 * - Blocking twice is harmless (one row per pair).
 * - Pending interests between the two members are cancelled (actor: system),
 *   with a history row. An accepted interest keeps its status, but photos and
 *   contact details close at once because BlockGuard is checked on every request.
 * - The activity log gets the public code only, never a name or contact detail.
 */
final class BlockMember
{
    public function __construct(private readonly InterestTransitions $transitions) {}

    public function __invoke(User $user, string $code): void
    {
        DB::transaction(function () use ($user, $code): void {
            $target = Biodata::query()->searchable()->where('public_code', $code)->first();

            if ($target === null) {
                throw ValidationException::withMessages(['form' => __('blocks.errors.unavailable')]);
            }

            Gate::forUser($user)->authorize('create', [Block::class, $target]);

            $created = $this->createBlock($user, (int) $target->user_id);

            $this->cancelPendingInterests($user, $target);

            if ($created) {
                activity('block')
                    ->causedBy($user)
                    ->withProperties(['target_code' => $target->public_code])
                    ->log('block.created');
            }
        });
    }

    /** @return bool true when a new row was written */
    private function createBlock(User $user, int $blockedUserId): bool
    {
        $exists = Block::query()
            ->where('blocker_user_id', $user->getKey())
            ->where('blocked_user_id', $blockedUserId)
            ->exists();

        if ($exists) {
            return false;
        }

        $max = (int) config('lifepartner.blocks.max');

        if (Block::query()->where('blocker_user_id', $user->getKey())->count() >= $max) {
            throw ValidationException::withMessages(['form' => __('blocks.errors.limit', ['max' => $max])]);
        }

        try {
            $block = new Block;
            $block->forceFill([
                'blocker_user_id' => $user->getKey(),
                'blocked_user_id' => $blockedUserId,
            ])->save();
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    private function cancelPendingInterests(User $user, Biodata $target): void
    {
        $own = Biodata::query()->where('user_id', $user->getKey())->first();

        if ($own === null) {
            return;
        }

        $pending = Interest::query()
            ->where('status', InterestStatus::Pending->value)
            ->where(function ($query) use ($own, $target): void {
                $query->where(fn ($pair) => $pair->where('sender_biodata_id', $own->getKey())->where('receiver_biodata_id', $target->getKey()))
                    ->orWhere(fn ($pair) => $pair->where('sender_biodata_id', $target->getKey())->where('receiver_biodata_id', $own->getKey()));
            })
            ->lockForUpdate()
            ->get();

        foreach ($pending as $interest) {
            $this->transitions->move($interest, InterestStatus::Cancelled, InterestActor::System, $user);
        }
    }
}
