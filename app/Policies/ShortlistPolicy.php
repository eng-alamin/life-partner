<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Biodata;
use App\Models\User;
use App\Services\Search\BiodataSearch;
use App\Services\Search\BiodataVisibility;

/**
 * Who may shortlist or ignore a biodata. Both lists use this one rule: the
 * viewer must be allowed to open that profile (active verified member, approved
 * biodata of the opposite type, no block) and it must not be their own.
 */
class ShortlistPolicy
{
    public function __construct(
        private readonly BiodataVisibility $visibility,
        private readonly BiodataSearch $search,
    ) {}

    public function add(User $user, Biodata $target): bool
    {
        return (int) $user->getKey() !== (int) $target->user_id
            && $this->visibility->canViewProfile($user, $target, $this->search);
    }
}
