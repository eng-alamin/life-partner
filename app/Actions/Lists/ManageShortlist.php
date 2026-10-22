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
 * Adds a biodata to, or removes it from, the signed-in member's shortlist.
 * Adding is idempotent and takes the biodata off the ignore list (a shortlisted
 * biodata should not be hidden from search). Removing touches only the caller's own rows.
 */
final class ManageShortlist
{
    public function __construct(private readonly ListTargets $targets) {}

    public function add(User $user, string $code): void
    {
        DB::transaction(function () use ($user, $code): void {
            $target = $this->targets->eligible($user, $code);

            $exists = Shortlist::query()
                ->where('user_id', $user->getKey())
                ->where('biodata_id', $target->getKey())
                ->exists();

            if (! $exists) {
                $max = (int) config('lifepartner.lists.shortlist_max');

                if (Shortlist::query()->where('user_id', $user->getKey())->count() >= $max) {
                    throw ValidationException::withMessages(['form' => __('lists.errors.shortlist_limit', ['max' => $max])]);
                }

                try {
                    $row = new Shortlist;
                    $row->forceFill(['user_id' => $user->getKey(), 'biodata_id' => $target->getKey()])->save();
                } catch (UniqueConstraintViolationException) {
                    // A second tap at the same moment: the row already exists, which is what we wanted.
                }
            }

            IgnoredBiodata::query()
                ->where('user_id', $user->getKey())
                ->where('biodata_id', $target->getKey())
                ->delete();
        });
    }

    public function remove(User $user, string $code): void
    {
        $target = $this->targets->anyByCode($code);

        if ($target === null) {
            return;
        }

        Shortlist::query()
            ->where('user_id', $user->getKey())
            ->where('biodata_id', $target->getKey())
            ->delete();
    }
}
