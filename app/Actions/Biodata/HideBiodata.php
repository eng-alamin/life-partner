<?php

declare(strict_types=1);

namespace App\Actions\Biodata;

use App\Enums\HiddenSource;
use App\Models\Biodata;
use App\Models\User;
use App\Services\Biodata\BiodataHiding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * A member hides their own approved biodata. The biodata is found from the signed-in
 * user, never from an id sent by the browser. Pending interests are cancelled.
 * The activity log gets the action name only.
 */
final class HideBiodata
{
    public function __construct(private readonly BiodataHiding $hiding) {}

    public function __invoke(User $user): Biodata
    {
        return DB::transaction(function () use ($user): Biodata {
            $biodata = Biodata::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            abort_if($biodata === null, 404);

            Gate::forUser($user)->authorize('hide', $biodata);

            $this->hiding->hide($biodata, HiddenSource::Owner, $user);

            activity('biodata')
                ->causedBy($user)
                ->performedOn($biodata)
                ->withProperties(['action' => 'hidden', 'by' => 'owner'])
                ->log('biodata.hidden');

            return $biodata;
        });
    }
}
