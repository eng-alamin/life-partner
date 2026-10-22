<?php

declare(strict_types=1);

namespace App\Actions\Biodata;

use App\Enums\DeactivationReason;
use App\Models\Biodata;
use App\Models\User;
use App\Services\Biodata\BiodataHiding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * A member pauses their own biodata ("I am not looking now"). The biodata is found from the
 * signed-in user, never from an id sent by the browser. Pending interests are cancelled and
 * nobody is told. The optional reason must be one of DeactivationReason; it is stored for
 * statistics and never logged.
 */
final class DeactivateBiodata
{
    public function __construct(private readonly BiodataHiding $hiding) {}

    public function __invoke(User $user, ?string $reason = null): Biodata
    {
        $parsed = $this->parse($reason);

        return DB::transaction(function () use ($user, $parsed): Biodata {
            $biodata = Biodata::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            abort_if($biodata === null, 404);

            Gate::forUser($user)->authorize('deactivate', $biodata);

            $this->hiding->deactivate($biodata, $user, $parsed);

            activity('biodata')
                ->causedBy($user)
                ->performedOn($biodata)
                ->withProperties(['action' => 'deactivated', 'by' => 'owner'])
                ->log('biodata.deactivated');

            return $biodata;
        });
    }

    private function parse(?string $reason): ?DeactivationReason
    {
        if ($reason === null || $reason === '') {
            return null;
        }

        return DeactivationReason::tryFrom($reason)
            ?? throw ValidationException::withMessages(['reason' => __('visibility.deactivate.reason_invalid')]);
    }
}
