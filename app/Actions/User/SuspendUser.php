<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use App\Enums\UserStatus;
use App\Mail\AccountStatusMail;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;
use App\Services\Interest\InterestTransitions;
use App\Services\Settings\SiteSettings;
use App\Support\Digits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

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
 * - The pending interests of the member (sent and received) are cancelled at once (actor: system), so nobody
 *   can accept a request from, or wait for an answer of, an account that can not sign in. Reactivating does
 *   NOT bring them back: people can send new ones. Accepted interests stay as they are.
 * - The member gets one email (no reason in it) saying the account is suspended and how to reach support.
 * - The activity log holds the staff member, the account and the word "suspended" only.
 */
final class SuspendUser
{
    public const REASON_MIN = 3;

    public const REASON_MAX = 500;

    public function __construct(private readonly InterestTransitions $transitions) {}

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

            $this->cancelPendingInterests($target, $staff);

            activity('user')
                ->causedBy($staff)
                ->performedOn($target)
                ->withProperties(['action' => 'suspended'])
                ->log('user.suspended');

            return $target;
        });

        $this->endSessions($target);
        $this->notify($target);

        return $target;
    }

    /** Must run inside the transaction of the suspension. */
    private function cancelPendingInterests(User $target, User $staff): void
    {
        $biodataId = Biodata::withTrashed()->where('user_id', $target->getKey())->value('id');

        if ($biodataId === null) {
            return;
        }

        $pending = Interest::query()
            ->where('status', InterestStatus::Pending->value)
            ->where(function ($query) use ($biodataId): void {
                $query->where('sender_biodata_id', $biodataId)->orWhere('receiver_biodata_id', $biodataId);
            })
            ->lockForUpdate()
            ->get();

        foreach ($pending as $interest) {
            $this->transitions->move($interest, InterestStatus::Cancelled, InterestActor::System, $staff);
        }
    }

    /** After the commit. A mail problem is reported and never undoes the suspension. */
    private function notify(User $target): void
    {
        try {
            Mail::to($target->email)
                ->locale($target->preferredLocale())
                ->queue(new AccountStatusMail($target->name, AccountStatusMail::SUSPENDED, app(SiteSettings::class)->contactEmail()));
        } catch (Throwable $exception) {
            report($exception);
        }
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
