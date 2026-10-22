<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Lifts a suspension. The suspension reason and date are cleared. The member's biodata
 * appears again at once because it was never changed. The activity log holds only the
 * staff member, the account and the word "reactivated".
 */
final class ReactivateUser
{
    public function __invoke(User $staff, int $targetId): User
    {
        Gate::forUser($staff)->authorize('viewAny', User::class);

        return DB::transaction(function () use ($staff, $targetId): User {
            $target = User::query()->whereKey($targetId)->lockForUpdate()->first();

            abort_if($target === null, 404);

            if ($target->status === UserStatus::Active) {
                throw ValidationException::withMessages(['form' => __('admin_users.errors.already_active')]);
            }

            Gate::forUser($staff)->authorize('reactivate', $target);

            $target->forceFill([
                'status' => UserStatus::Active,
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_reason' => null,
            ])->save();

            activity('user')
                ->causedBy($staff)
                ->performedOn($target)
                ->withProperties(['action' => 'reactivated'])
                ->log('user.reactivated');

            return $target;
        });
    }
}
