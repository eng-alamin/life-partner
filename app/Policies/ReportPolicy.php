<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\Biodata;
use App\Models\Report;
use App\Models\User;

/**
 * Member side: who may report whom. A block does not stop a report: a member
 * who was harassed and then blocked the other person must still be able to tell staff.
 *
 * Staff side: who may see and decide reports. Needs the reports permission and
 * an active account, and nobody decides a report about their own biodata.
 */
class ReportPolicy
{
    /** An active member with a verified email may report another member's biodata, never their own. */
    public function create(User $user, Biodata $target): bool
    {
        return $user->isActive()
            && $user->hasVerifiedEmail()
            && (int) $user->getKey() !== (int) $target->user_id;
    }

    /** Open the report queue. */
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::HandleReports->value);
    }

    /** Open one report and decide it (dismiss, resolve, hide the biodata). */
    public function handle(User $user, Report $report): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($report->biodata_id === null) {
            return true;
        }

        // A query instead of $report->biodata: lazy loading is switched off outside production.
        $ownerId = Biodata::query()->whereKey($report->biodata_id)->value('user_id');

        return $ownerId === null || (int) $ownerId !== (int) $user->getKey();
    }
}
