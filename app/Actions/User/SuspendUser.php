<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Digits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Suspends a member account.
 *
 * - Only an active admin with users.manage; UserPolicy::suspend adds the safety rules
 *   (not yourself, not a staff account, not an account that is already suspended).
 * - A reason (3 to 500 characters) is required. It is saved for staff and never logged.
 * - The remember-me token is replaced and every stored session of the member is deleted,
 *   so the person is signed out at once. EnsureAccountActive covers other session drivers.
 * - The biodata is not changed: it simply stops appearing while the owner is suspended
 *   (see Biodata::scopeSearchable) and returns when the account is reactivated.
 * - The activity log holds the staff member, the account and the word "suspended" only.
 */
final class SuspendUser
{
    public const REASON_MIN = 3;

    public const REASON_MAX = 500;

    public function __invoke(User $staff, int $targetId, string $reason): User
    {
        Gate::forUser($staff)->authorize('viewAny', User::class);

        $clean = $this->reason($reason);

        $target = DB::transaction(function () use ($staff, $targetId, $clean): User {
            $target = User::query()->whereKey($targetId)->lockForUpdate()->first();

            abort_if($target === null, 404);

            if ($target->status === UserStatus::Suspended) {
                throw ValidationException::withMessages(['form' => __('admin_users.errors.already_suspended')]);
            }

            Gate::forUser($staff)->authorize('suspend', $target);

            $target->forceFill([
                'status' => UserStatus::Suspended,
                'suspended_at' => now(),
                'suspended_by' => $staff->getKey(),
                'suspension_reason' => $clean,
                'remember_token' => Str::random(60),
            ])->save();

            activity('user')
                ->causedBy($staff)
                ->performedOn($target)
                ->withProperties(['action' => 'suspended'])
                ->log('user.suspended');

            return $target;
        });

        $this->endSessions($target);

        return $target;
    }

    private function reason(string $value): string
    {
        $clean = preg_replace('/\p{Cc}+/u', ' ', $value);
        $clean = trim(preg_replace('/\s+/u', ' ', (string) $clean) ?? '');

        if ($clean === '' || mb_strlen($clean) < self::REASON_MIN || mb_strlen($clean) > self::REASON_MAX) {
            throw ValidationException::withMessages([
                'reason' => __('admin_users.errors.reason_invalid', [
                    'min' => Digits::localize(self::REASON_MIN),
                    'max' => Digits::localize(self::REASON_MAX),
                ]),
            ]);
        }

        return $clean;
    }

    /** Deletes the stored sessions of this member (database session driver only). */
    private function endSessions(User $target): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table((string) config('session.table', 'sessions'))
            ->where('user_id', $target->getKey())
            ->delete();
    }
}
