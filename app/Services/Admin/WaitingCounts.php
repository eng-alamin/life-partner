<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\BiodataStatus;
use App\Enums\IdentityStatus;
use App\Enums\PaymentStatus;
use App\Enums\PhotoStatus;
use App\Enums\ReportStatus;
use App\Enums\StaffPermission;
use App\Models\Biodata;
use App\Models\BiodataPhoto;
use App\Models\IdentityVerification;
use App\Models\Payment;
use App\Models\Report;
use App\Models\User;

/**
 * How many things wait for one staff member. A number is returned only for what
 * this person may actually handle, and it matches the queue the person would open
 * (a reviewer's own biodata, photos and identity request are never in their queue,
 * so never counted). Two keys point to the payments page: "payments" (manual
 * payments under review) and "payments_attention" (bKash payments flagged for staff).
 */
final class WaitingCounts
{
    /** Count key => the admin page that lists it. */
    public const ROUTES = [
        'biodata' => 'admin.approvals',
        'photos' => 'admin.photos',
        'payments' => 'admin.payments',
        'payments_attention' => 'admin.payments',
        'reports' => 'admin.reports',
        'identity' => 'admin.verification',
    ];

    /** @return array<string, int> only the keys this person may handle */
    public function for(User $user): array
    {
        $counts = [];

        if ($user->can(StaffPermission::ReviewBiodata->value)) {
            $counts['biodata'] = Biodata::query()
                ->where('status', BiodataStatus::Pending->value)
                ->where('user_id', '!=', $user->getKey())
                ->count();

            $counts['photos'] = BiodataPhoto::query()
                ->where('status', PhotoStatus::Pending->value)
                ->whereHas('biodata', fn ($query) => $query->where('user_id', '!=', $user->getKey()))
                ->count();
        }

        if ($user->can('viewAny', Payment::class)) {
            $counts['payments'] = Payment::query()
                ->where('status', PaymentStatus::UnderReview->value)
                ->count();

            // bKash payments where money may have moved but nothing was granted.
            $counts['payments_attention'] = Payment::query()->needsAttention()->count();
        }

        if ($user->can('viewAny', Report::class)) {
            $counts['reports'] = Report::query()
                ->where('status', ReportStatus::Open->value)
                ->count();
        }

        if ($user->can('viewAny', IdentityVerification::class)) {
            $counts['identity'] = IdentityVerification::query()
                ->where('status', IdentityStatus::Pending->value)
                ->where('user_id', '!=', $user->getKey())
                ->count();
        }

        return $counts;
    }
}
