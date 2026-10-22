<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Models\SavedSearch;
use App\Models\User;
use App\Services\Search\SearchCriteria;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Saves a member's filters under a name. At most 10 per member and the name is
 * unique per member. Errors use the key `saveName` (the page field).
 */
final class SaveSearch
{
    public const MAX_PER_MEMBER = 10;

    private const NAME_PATTERN = '/^[\p{L}\p{M}\p{N} .,\x27\x{2019}()&\/-]+$/u';

    public function __invoke(User $user, string $name, SearchCriteria $criteria): SavedSearch
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        Validator::make(
            ['saveName' => $name],
            ['saveName' => ['required', 'string', 'min:2', 'max:60', 'regex:'.self::NAME_PATTERN]],
            [],
            ['saveName' => __('search.saved.name_label')],
        )->validate();

        $filters = $criteria->toArray(forStorage: true);

        if ($filters === []) {
            throw ValidationException::withMessages(['saveName' => __('search.saved.no_filters')]);
        }

        try {
            return DB::transaction(function () use ($user, $name, $filters): SavedSearch {
                // Locks the member's row so two quick saves cannot pass the limit together.
                User::query()->whereKey($user->getKey())->lockForUpdate()->first();

                $searches = $user->savedSearches();

                if ($searches->count() >= self::MAX_PER_MEMBER) {
                    throw ValidationException::withMessages([
                        'saveName' => __('search.saved.limit', ['max' => self::MAX_PER_MEMBER]),
                    ]);
                }

                if ($searches->where('name', $name)->exists()) {
                    throw ValidationException::withMessages(['saveName' => __('search.saved.duplicate')]);
                }

                return $user->savedSearches()->create(['name' => $name, 'filters' => $filters]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['saveName' => __('search.saved.duplicate')]);
        }
    }
}
