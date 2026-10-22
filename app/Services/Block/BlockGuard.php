<?php

declare(strict_types=1);

namespace App\Services\Block;

use App\Models\Biodata;
use App\Models\Block;
use Illuminate\Database\Eloquent\Builder;

/**
 * The single place that answers "is there a block between these two members?".
 * A block works in both directions: the blocker and the blocked member can no
 * longer see each other in search, profiles, photos, interests or contact.
 */
final class BlockGuard
{
    /** True when either user has blocked the other. */
    public function between(int|string $userA, int|string $userB): bool
    {
        $a = (int) $userA;
        $b = (int) $userB;

        if ($a === $b) {
            return false;
        }

        return Block::query()
            ->where(function ($query) use ($a, $b): void {
                $query->where(fn ($pair) => $pair->where('blocker_user_id', $a)->where('blocked_user_id', $b))
                    ->orWhere(fn ($pair) => $pair->where('blocker_user_id', $b)->where('blocked_user_id', $a));
            })
            ->exists();
    }

    /** Same question for two biodata ids (interests work on biodatas). Deleted biodatas still count. */
    public function betweenBiodatas(int $biodataA, int $biodataB): bool
    {
        $owners = Biodata::withTrashed()
            ->whereIn('id', [$biodataA, $biodataB])
            ->pluck('user_id', 'id');

        if ($owners->count() !== 2) {
            return false;
        }

        return $this->between((int) $owners[$biodataA], (int) $owners[$biodataB]);
    }

    /**
     * Every user that has a block with this user, in either direction.
     *
     * @return list<int>
     */
    public function hiddenUserIds(int $userId): array
    {
        $blockedByMe = Block::query()->where('blocker_user_id', $userId)->pluck('blocked_user_id');
        $blockedMe = Block::query()->where('blocked_user_id', $userId)->pluck('blocker_user_id');

        return $blockedByMe->merge($blockedMe)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Removes biodatas of blocked members from a biodata query (both directions).
     * Uses sub-queries, so the number of blocks does not matter.
     *
     * @param  Builder<Biodata>  $query
     * @return Builder<Biodata>
     */
    public function excludeFor(Builder $query, int $userId): Builder
    {
        return $query
            ->whereNotIn('user_id', Block::query()->where('blocker_user_id', $userId)->select('blocked_user_id'))
            ->whereNotIn('user_id', Block::query()->where('blocked_user_id', $userId)->select('blocker_user_id'));
    }
}
