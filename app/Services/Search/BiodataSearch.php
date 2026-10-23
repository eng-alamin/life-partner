<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Enums\BiodataType;
use App\Enums\PracticingLevel;
use App\Models\Biodata;
use App\Models\IgnoredBiodata;
use App\Models\User;
use App\Services\Block\BlockGuard;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Runs the member search. Only approved biodatas can appear, the viewer's own
 * biodata never does, and the columns read are limited to what a card shows.
 */
final class BiodataSearch
{
    public const PER_PAGE = 12;

    public const GUEST_MAX_PAGES = 3;

    /** Stops absurd offsets from a hand-edited URL. */
    public const MAX_PAGE = 500;

    /** Only what a card needs. Employer, guardian and review fields are never loaded. */
    private const CARD_COLUMNS = [
        'id', 'public_code', 'type', 'birth_date', 'marital_status',
        'present_country', 'present_district_id', 'profession', 'practicing_level', 'reviewed_at', 'photo_visibility',
        'identity_verified_at',
    ];

    public function __construct(private readonly BlockGuard $blocks) {}

    /**
     * The type this viewer searches. A member always sees the opposite of their
     * own biodata and has none until a biodata with a type is started. A guest
     * picks the type.
     */
    public function typeFor(?User $member, SearchCriteria $criteria): ?BiodataType
    {
        if ($member === null) {
            return $criteria->type;
        }

        return $this->ownType($member)?->opposite();
    }

    public function ownType(User $member): ?BiodataType
    {
        $type = Biodata::query()->where('user_id', $member->getKey())->value('type');

        return $type instanceof BiodataType ? $type : BiodataType::tryFrom((string) $type);
    }

    /**
     * @param  User|null  $member  a signed-in, active, verified member, or null for a guest
     * @return Paginator<int, Biodata>|null null when the type is not known yet
     */
    public function search(SearchCriteria $criteria, ?User $member, int $page): ?Paginator
    {
        $type = $this->typeFor($member, $criteria);

        if ($type === null) {
            return null;
        }

        if ($member === null) {
            $criteria = $criteria->forGuest();
            $page = min(max($page, 1), self::GUEST_MAX_PAGES);
        } else {
            $page = min(max($page, 1), self::MAX_PAGE);
        }

        return $this->query($criteria, $type, $member?->getKey())
            ->simplePaginate(self::PER_PAGE, self::CARD_COLUMNS, 'page', $page);
    }

    /** @return Builder<Biodata> */
    private function query(SearchCriteria $criteria, BiodataType $type, int|string|null $excludeUserId): Builder
    {
        $query = Biodata::query()
            ->searchable()
            ->where('type', $type->value)
            ->with(['presentDistrict', 'mainPhoto']);

        if ($excludeUserId !== null) {
            $query->where('user_id', '!=', $excludeUserId);
            // Members who blocked the viewer, or whom the viewer blocked, never appear.
            $this->blocks->excludeFor($query, (int) $excludeUserId);
            // Biodatas this member chose to ignore stay out of their own search (one-sided).
            $query->whereNotIn('id', IgnoredBiodata::query()->where('user_id', $excludeUserId)->select('biodata_id'));
        }

        // Age becomes a birth-date range so the (status, type, birth_date) index is used.
        if ($criteria->ageMin !== null) {
            $query->where('birth_date', '<=', today()->subYearsNoOverflow($criteria->ageMin)->toDateString());
        }

        if ($criteria->ageMax !== null) {
            $query->where('birth_date', '>', today()->subYearsNoOverflow($criteria->ageMax + 1)->toDateString());
        }

        if ($criteria->districtId !== null) {
            $query->where('present_district_id', $criteria->districtId);
        }

        if ($criteria->heightMin !== null) {
            $query->where('height_cm', '>=', $criteria->heightMin);
        }

        if ($criteria->heightMax !== null) {
            $query->where('height_cm', '<=', $criteria->heightMax);
        }

        if ($criteria->maritalStatus !== null) {
            $query->where('marital_status', $criteria->maritalStatus->value);
        }

        if ($criteria->country !== null) {
            $query->where('present_country', $criteria->country);
        }

        if ($criteria->minEducation !== null) {
            $query->where('highest_education_rank', '>=', $criteria->minEducation->rank());
        }

        if ($criteria->practicingLevel !== null) {
            $minimum = $criteria->practicingLevel->rank();

            $query->whereIn('practicing_level', array_map(
                fn (PracticingLevel $level): string => $level->value,
                array_filter(PracticingLevel::cases(), fn (PracticingLevel $level): bool => $level->rank() >= $minimum),
            ));
        }

        // Last, so the indexed filters above narrow the rows first.
        if ($criteria->profession !== null) {
            $query->whereRaw("profession LIKE ? ESCAPE '!'", ['%'.$this->escapeLike($criteria->profession).'%']);
        }

        return $query
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id');
    }

    /** Makes %, _ and the escape character itself match literally. */
    private function escapeLike(string $text): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text);
    }
}
