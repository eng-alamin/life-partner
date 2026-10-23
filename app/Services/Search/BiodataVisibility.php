<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Enums\BiodataStatus;
use App\Enums\PhotoStatus;
use App\Enums\PhotoVisibility;
use App\Enums\StaffPermission;
use App\Models\Biodata;
use App\Models\BiodataEducation;
use App\Models\BiodataPhoto;
use App\Models\User;
use App\Services\Block\BlockGuard;
use App\Services\Interest\InterestConnections;
use App\Support\Countries;

/**
 * The single place that decides which fields of a biodata another person may
 * see. Pages never read model attributes for display on their own: they show
 * what card() or profile() returns, and nothing else.
 *
 * - Card (guests and members): code, type, age, marital status, place,
 *   profession, practicing level. Never the birth date.
 * - Profile (members only): adds height, education, religion, family, about me,
 *   marriage plans and partner preferences.
 * - Never shown to others: employer, income, guardian, name, email, review fields.
 *   The owner also sees their own employer.
 * - Photos: never for guests. A member sees an approved photo only when the
 *   biodata is approved, the owner chose "approved members", and the member
 *   belongs to the opposite type. The owner sees their own photos; staff see
 *   photos so they can review them. Everything else is simply not found.
 *   If the owner chose "after acceptance", the member also needs an accepted
 *   interest with the owner that is open (same rule as the guardian contact).
 *   Search cards never show "after acceptance" photos; the profile page does.
 * - A "paused" member (own biodata deactivated or hidden, or waiting for account
 *   deletion) sees nothing of other members: no photos, no full profiles, and the
 *   search and shortlist pages stay empty for them (see isPaused()).
 */
final class BiodataVisibility
{
    public function __construct(
        private readonly InterestConnections $connections,
        private readonly BlockGuard $blocks,
    ) {}

    /** Only a signed-in, active member with a verified email counts as a member. */
    public function memberOrNull(?User $user): ?User
    {
        return $user !== null && $user->isActive() && $user->hasVerifiedEmail() ? $user : null;
    }

    /** Approved biodatas are public as cards. Anything else is visible to its owner only. */
    public function canView(?User $viewer, Biodata $biodata): bool
    {
        if ($biodata->status->isSearchable()) {
            return true;
        }

        return $viewer !== null && $viewer->getKey() === $biodata->user_id;
    }

    /**
     * The full profile needs a member. A member sees the opposite type of their
     * own biodata (the same rule as search); the owner always sees their own.
     */
    public function canViewProfile(?User $user, Biodata $biodata, BiodataSearch $search): bool
    {
        $member = $this->memberOrNull($user);

        if ($member === null || ! $this->canView($member, $biodata)) {
            return false;
        }

        if ($member->getKey() === $biodata->user_id) {
            return true;
        }

        // A suspended member's profile is gone for everyone else (same answer as "not found").
        if (! $biodata->ownerIsActive()) {
            return false;
        }

        // A block hides the profile for both sides (same answer as "not found").
        if ($this->blocks->between($member->getKey(), $biodata->user_id)) {
            return false;
        }

        // A paused member (deactivated, hidden or waiting for deletion) opens no other profile.
        if ($this->isPaused($member)) {
            return false;
        }

        $ownType = $search->ownType($member);

        return $ownType !== null && $biodata->type === $ownType->opposite();
    }

    /**
     * May this person receive the image file of this photo? Used by the photo
     * route on every request. Needs the `biodata` relation loaded.
     */
    public function canViewPhoto(?User $user, BiodataPhoto $photo, BiodataSearch $search): bool
    {
        $biodata = $photo->biodata;

        if ($user === null || ! $user->isActive() || $biodata === null) {
            return false;
        }

        if ($user->getKey() === $biodata->user_id) {
            return true;
        }

        // Reviewers must see a photo before it is approved.
        if ($user->checkPermissionTo(StaffPermission::ReviewBiodata->value)) {
            return true;
        }

        $member = $this->memberOrNull($user);

        if ($member === null || $this->isPaused($member) || $this->blocks->between($member->getKey(), $biodata->user_id) || ! $this->othersMayViewPhoto($member, $biodata, $photo)) {
            return false;
        }

        $ownType = $search->ownType($member);

        return $ownType !== null && $biodata->type === $ownType->opposite();
    }

    /**
     * Thumbnail address for a search card, or null. Search already limits
     * results to approved biodatas of the opposite type, and the `mainPhoto`
     * relation holds an approved photo only. Needs `mainPhoto` loaded.
     */
    public function cardPhotoUrl(?User $member, Biodata $biodata): ?string
    {
        if ($member === null || ! $biodata->relationLoaded('mainPhoto') || $biodata->mainPhoto === null) {
            return null;
        }

        if ($biodata->photo_visibility !== PhotoVisibility::ApprovedMembers) {
            return null;
        }

        return $this->photoUrl($biodata, $biodata->mainPhoto, 'thumb');
    }

    /**
     * Photos for the profile page (the caller already passed canViewProfile).
     * The owner sees their own photos that are not rejected; everyone else sees
     * approved photos only, and only if the owner allowed it. Needs `photos`
     * loaded. The main photo comes first.
     *
     * @return list<array{code: string, url: string, thumb: string, is_main: bool, pending: bool}>
     */
    public function profilePhotos(User $viewer, Biodata $biodata): array
    {
        $isOwner = $viewer->getKey() === $biodata->user_id;

        if (! $isOwner && ! $this->photosOpenTo($viewer, $biodata)) {
            return [];
        }

        $items = [];

        foreach ($biodata->photos as $photo) {
            $shown = $isOwner ? $photo->status !== PhotoStatus::Rejected : $photo->status === PhotoStatus::Approved;

            if (! $shown) {
                continue;
            }

            $items[] = [
                'code' => $photo->code,
                'url' => $this->photoUrl($biodata, $photo, 'large'),
                'thumb' => $this->photoUrl($biodata, $photo, 'thumb'),
                'is_main' => (bool) $photo->is_main,
                'pending' => $photo->status === PhotoStatus::Pending,
            ];
        }

        usort($items, fn (array $a, array $b): int => (int) $b['is_main'] <=> (int) $a['is_main']);

        return $items;
    }

    /** Address of an image through the authorized route. Never a file path. */
    public function photoUrl(Biodata $biodata, BiodataPhoto $photo, string $size): string
    {
        return route('photos.show', [
            'biodata' => $biodata->public_code,
            'photo' => $photo->code,
            'size' => $size,
        ]);
    }

    private function othersMayViewPhoto(User $member, Biodata $biodata, BiodataPhoto $photo): bool
    {
        return $biodata->status->isSearchable()
            && $photo->status === PhotoStatus::Approved
            && $biodata->ownerIsActive()
            && $this->photosOpenTo($member, $biodata);
    }

    /**
     * A paused member: waiting for account deletion, or their own biodata is
     * deactivated or hidden (by themselves or by staff). A paused member may not
     * look at other people's profiles, photos or search results. Draft, pending
     * and rejected members are not paused: they still need to browse.
     * Checked on every request, never cached.
     */
    public function isPaused(User $member): bool
    {
        if ($member->hasDeletionRequested()) {
            return true;
        }

        return Biodata::query()
            ->where('user_id', $member->getKey())
            ->whereIn('status', [BiodataStatus::Deactivated->value, BiodataStatus::Hidden->value])
            ->exists();
    }

    /** Has the owner opened their photos to this member? Checked on every request. */
    private function photosOpenTo(User $member, Biodata $owner): bool
    {
        return match ($owner->photo_visibility) {
            PhotoVisibility::ApprovedMembers => true,
            PhotoVisibility::AfterAcceptance => $this->isConnected($member, $owner),
            default => false,
        };
    }

    /** The member needs their own biodata (not deactivated or hidden) and an open accepted interest with the owner. */
    private function isConnected(User $member, Biodata $owner): bool
    {
        $ownId = Biodata::query()
            ->where('user_id', $member->getKey())
            ->whereNotIn('status', [BiodataStatus::Deactivated->value, BiodataStatus::Hidden->value])
            ->value('id');

        return $ownId !== null && $this->connections->areConnected((int) $ownId, (int) $owner->getKey());
    }

    /**
     * Needs `presentDistrict` loaded.
     *
     * @return array{code: string, type: string, age: ?int, marital_status: ?string, location: string, profession: ?string, practicing_level: ?string}
     */
    public function card(Biodata $biodata): array
    {
        return [
            'code' => $biodata->public_code,
            'type' => (string) $biodata->type?->label(),
            'age' => $biodata->age,
            'marital_status' => $biodata->marital_status?->label(),
            'location' => $this->location($biodata),
            'profession' => $biodata->profession,
            'practicing_level' => $biodata->practicing_level?->label(),
            'identity_verified' => $biodata->isIdentityVerified(),
        ];
    }

    /**
     * Needs `presentDistrict`, `educations` and `preference.preferredDistrict` loaded.
     * Each section is a list of label/value rows; empty values are left out.
     *
     * @return array<string, list<array{label: string, value: string}>>
     */
    public function profile(Biodata $biodata, User $viewer): array
    {
        $isOwner = $viewer->getKey() === $biodata->user_id;
        $preference = $biodata->preference;

        $sections = [
            'general' => $this->rows([
                'type' => $biodata->type?->label(),
                'age' => $biodata->age,
                'marital_status' => $biodata->marital_status?->label(),
                'height_cm' => $biodata->height_cm,
                'location' => $this->location($biodata),
            ]),
            'work' => $this->rows([
                'profession' => $biodata->profession,
                'employer' => $isOwner ? $biodata->employer : null,
            ]),
            'religion' => $this->rows([
                'prayer_habit' => $biodata->prayer_habit?->label(),
                'quran_reading' => $biodata->quran_reading?->label(),
                'madhhab' => $biodata->madhhab?->label(),
                'practicing_level' => $biodata->practicing_level?->label(),
                'dress_practice' => $biodata->dress_practice?->label(),
            ]),
            'family' => $this->rows([
                'father_status' => $biodata->father_status?->label(),
                'mother_status' => $biodata->mother_status?->label(),
                'brothers_count' => $biodata->brothers_count,
                'sisters_count' => $biodata->sisters_count,
                'family_financial_status' => $biodata->family_financial_status?->label(),
            ]),
            'personal' => $this->rows([
                'about_me' => $biodata->about_me,
                'hobbies' => $biodata->hobbies,
            ]),
            'marriage' => $this->rows([
                'marriage_timeline' => $biodata->marriage_timeline?->label(),
                'spouse_work_after_marriage' => $biodata->spouse_work_after_marriage?->label(),
                'living_arrangement' => $biodata->living_arrangement?->label(),
            ]),
            'preferences' => $preference === null ? [] : $this->rows([
                'age_min' => $preference->age_min,
                'age_max' => $preference->age_max,
                'height_min_cm' => $preference->height_min_cm,
                'min_education_level' => $preference->min_education_level?->label(),
                'preferred_country' => Countries::name($preference->preferred_country) ?: null,
                'preferred_district_id' => $preference->preferredDistrict?->name(),
                'marital_statuses' => $preference->marital_statuses?->map(fn ($status): string => $status->label())->implode(', '),
                'min_practicing_level' => $preference->min_practicing_level?->label(),
            ]),
        ];

        return array_filter($sections, fn (array $rows): bool => $rows !== []);
    }

    /**
     * Degree rows for the education section.
     *
     * @return list<array{degree: string, level: ?string, institution: string, year: int}>
     */
    public function educations(Biodata $biodata): array
    {
        return $biodata->educations
            ->map(fn (BiodataEducation $row): array => [
                'degree' => $row->degree,
                'level' => $row->level?->label(),
                'institution' => $row->institution,
                'year' => (int) $row->passing_year,
            ])
            ->values()
            ->all();
    }

    private function location(Biodata $biodata): string
    {
        $country = Countries::name($biodata->present_country);
        $district = $biodata->presentDistrict?->name();

        return $district !== null && $district !== '' ? $district.', '.$country : $country;
    }

    /**
     * @param  array<string, string|int|null>  $values  biodata.fields key => value
     * @return list<array{label: string, value: string}>
     */
    private function rows(array $values): array
    {
        $rows = [];

        foreach ($values as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $rows[] = [
                'label' => __('biodata.fields.'.$field),
                'value' => (string) $value,
            ];
        }

        return $rows;
    }
}
