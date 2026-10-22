<?php

declare(strict_types=1);

namespace App\Actions\Biodata;

use App\Enums\BiodataStatus;
use App\Enums\NotificationType;
use App\Models\Biodata;
use App\Models\User;
use App\Services\Biodata\BiodataHiding;
use App\Services\Notification\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * A member shows their own biodata again, but only if THEY hid it (a staff-hide can not be
 * undone here). It returns straight to approved when nothing changed while hidden;
 * otherwise it goes back to the approval queue.
 */
final class UnhideBiodata
{
    public function __construct(
        private readonly BiodataHiding $hiding,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $user): BiodataStatus
    {
        $status = DB::transaction(function () use ($user): BiodataStatus {
            $biodata = Biodata::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            abort_if($biodata === null, 404);

            Gate::forUser($user)->authorize('unhide', $biodata);

            $to = $this->hiding->restore($biodata);

            activity('biodata')
                ->causedBy($user)
                ->performedOn($biodata)
                ->withProperties(['action' => 'shown', 'by' => 'owner', 'to' => $to->value])
                ->log('biodata.shown');

            return $to;
        });

        // After the transaction, so a rolled-back change tells nobody.
        if ($status === BiodataStatus::Pending) {
            $this->notifier->toStaff(NotificationType::StaffBiodataChanged);
        }

        return $status;
    }
}
