<?php

declare(strict_types=1);

namespace App\Actions\Lists;

use App\Models\IgnoredBiodata;
use App\Models\Shortlist;
use App\Models\User;
use App\Services\Lists\ListTargets;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hides a biodata from the signed-in member's own search, or shows it again.
 * One-sided: the other member is never told. Ignoring is idempotent and takes
 * the biodata off the shortlist. Restoring touches only the caller's own rows.
 */
final class ManageIgnoreList
{
    public function __construct(private readonly ListTargets $targets) {}

    public function ignore(User $user, string $code): void
    {
        DB::transaction(function () use ($user, $code): void {
            $target = $this->targets->eligible($user, $code);

            $exists = IgnoredBiodata::query()
                ->where('user_id', $user->getKey())
                ->where('biodata_id', $target->getKey())
                ->exists();

            if (! $exists) {
                $max = (int) config('lifepartner.lists.ignore_max');

                if (IgnoredBiodata::query()->where('user_id', $user->getKey())->count() >= $max) {
                    throw ValidationException::withMessages(['form' => __('lists.errors.ignore_limit', ['max' => $max])]);
                }

                try {
                    $row = new IgnoredBiodata;
                    $row->forceFill(['user_id' => $user->getKey(), 'biodata_id' => $target->getKey()])->save();
                } catch (UniqueConstraintViolationException) {
                    // Already ignored: nothing more to do.
                }
            }

            Shortlist::query()
                ->where('user_id', $user->getKey())
                ->where('biodata_id', $target->getKey())
                ->delete();
        });
    }

    public function restore(User $user, string $code): void
    {
        $target = $this->targets->anyByCode($code);

        if ($target === null) {
            return;
        }

        IgnoredBiodata::query()
            ->where('user_id', $user->getKey())
            ->where('biodata_id', $target->getKey())
            ->delete();
    }
}
