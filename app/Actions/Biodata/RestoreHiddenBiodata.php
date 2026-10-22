<?php

declare(strict_types=1);

namespace App\Actions\Biodata;

use App\Enums\BiodataStatus;
use App\Models\Biodata;
use App\Models\User;
use App\Services\Biodata\BiodataHiding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Staff bring back a biodata that STAFF hid (or that was hidden before the source was recorded).
 * A biodata the member hid is the member's own choice and can not be restored here.
 * Returns to approved if nothing changed while hidden; otherwise to the approval queue.
 * The activity log gets the public code and the two statuses only.
 */
final class RestoreHiddenBiodata
{
    public function __construct(private readonly BiodataHiding $hiding) {}

    public function __invoke(User $staff, string $publicCode): BiodataStatus
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{6,20}$/', $publicCode) === 1, 404);

        return DB::transaction(function () use ($staff, $publicCode): BiodataStatus {
            $biodata = Biodata::query()
                ->where('public_code', $publicCode)
                ->lockForUpdate()
                ->first();

            abort_if($biodata === null, 404);

            Gate::forUser($staff)->authorize('restoreHidden', $biodata);

            $to = $this->hiding->restore($biodata);

            activity('moderation')
                ->causedBy($staff)
                ->performedOn($biodata)
                ->event('restored')
                ->withProperties([
                    'biodata_code' => $biodata->public_code,
                    'from' => BiodataStatus::Hidden->value,
                    'to' => $to->value,
                ])
                ->log('biodata.restored');

            return $to;
        });
    }
}
