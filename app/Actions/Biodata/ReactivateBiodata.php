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
 * A member turns their paused biodata on again. It returns to approved when it was approved
 * before the pause and nothing changed (editing is blocked while deactivated, so only a
 * change made while it was hidden earlier counts). Otherwise it goes back to the approval queue.
 */
final class ReactivateBiodata
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

            Gate::forUser($user)->authorize('reactivate', $biodata);

            $to = $this->hiding->restore($biodata);

            activity('biodata')
                ->causedBy($user)
                ->performedOn($biodata)
                ->withProperties(['action' => 'reactivated', 'by' => 'owner', 'to' => $to->value])
                ->log('biodata.reactivated');

            return $to;
        });

        if ($status === BiodataStatus::Pending) {
            $this->notifier->toStaff(NotificationType::StaffBiodataChanged);
        }

        return $status;
    }
}
