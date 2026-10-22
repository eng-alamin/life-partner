<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The member changes their mind during the waiting period. The account is the signed-in user.
 *
 * The biodata is NOT shown again by this action: it stays deactivated until the member
 * chooses "Reactivate" themselves, so a cancelled deletion never makes a profile public by itself.
 */
final class CancelAccountDeletion
{
    public function __invoke(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->hasDeletionRequested()) {
                throw ValidationException::withMessages(['form' => __('account_deletion.errors.not_requested')]);
            }

            $locked->forceFill([
                'deletion_requested_at' => null,
                'deletion_scheduled_for' => null,
            ])->save();

            activity('account')
                ->causedBy($locked)
                ->performedOn($locked)
                ->withProperties(['action' => 'deletion_cancelled'])
                ->log('account.deletion_cancelled');

            return $locked;
        });
    }
}
