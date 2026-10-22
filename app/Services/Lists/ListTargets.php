<?php

declare(strict_types=1);

namespace App\Services\Lists;

use App\Models\Biodata;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Finds the biodata behind a public code for the shortlist and ignore actions.
 * Every reason for refusal (unknown code, not approved, own biodata, same type,
 * blocked) gives the same message, so the answer reveals nothing.
 */
final class ListTargets
{
    public function eligible(User $user, string $code): Biodata
    {
        $target = Biodata::query()->searchable()->where('public_code', $code)->first();

        if ($target === null || ! Gate::forUser($user)->allows('add', [Shortlist::class, $target])) {
            throw ValidationException::withMessages(['form' => __('lists.errors.unavailable')]);
        }

        return $target;
    }

    /** For removing: the biodata may be hidden or deleted by now, so no eligibility check. */
    public function anyByCode(string $code): ?Biodata
    {
        return Biodata::withTrashed()->where('public_code', $code)->first();
    }
}
