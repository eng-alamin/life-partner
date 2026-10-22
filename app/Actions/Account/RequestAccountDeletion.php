<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\BiodataStatus;
use App\Enums\HiddenSource;
use App\Models\Biodata;
use App\Models\User;
use App\Services\Biodata\BiodataHiding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * A member asks to delete their own account. Nothing is deleted now: the data is purged
 * only after a waiting period (see WAIT_DAYS) and the member can cancel until then.
 *
 * - The account is always the signed-in user, never an id sent by the browser.
 * - The member proves who they are: the password, or, for an account that has no password
 *   (social sign-in), the account's own email address typed in full.
 * - Staff accounts cannot delete themselves: their role must be taken away first.
 * - An approved or pending biodata (or one the member hid) is deactivated at once, so it
 *   leaves search and its pending interests are cancelled. Nobody is told.
 *   A draft, a rejected biodata or one hidden by staff is already out of sight and is left alone.
 * - The activity log gets the action name only. The proof is never stored or logged.
 */
final class RequestAccountDeletion
{
    public const WAIT_DAYS = 14;

    public function __construct(private readonly BiodataHiding $hiding) {}

    public function __invoke(User $user, string $proof): User
    {
        return DB::transaction(function () use ($user, $proof): User {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->hasDeletionRequested()) {
                throw ValidationException::withMessages(['form' => __('account_deletion.errors.already_requested')]);
            }

            if ($locked->roles()->exists()) {
                throw ValidationException::withMessages(['form' => __('account_deletion.errors.staff')]);
            }

            $this->confirmIdentity($locked, $proof);

            $locked->forceFill([
                'deletion_requested_at' => now(),
                'deletion_scheduled_for' => now()->addDays(self::WAIT_DAYS),
            ])->save();

            $biodata = Biodata::query()->where('user_id', $locked->getKey())->lockForUpdate()->first();

            if ($biodata !== null && $this->shouldPause($biodata)) {
                $this->hiding->deactivate($biodata, $locked, null);
            }

            activity('account')
                ->causedBy($locked)
                ->performedOn($locked)
                ->withProperties(['action' => 'deletion_requested'])
                ->log('account.deletion_requested');

            return $locked;
        });
    }

    private function confirmIdentity(User $user, string $proof): void
    {
        $ok = filled($user->password)
            ? Hash::check($proof, (string) $user->password)
            : mb_strtolower(trim($proof)) === mb_strtolower((string) $user->email);

        if (! $ok) {
            throw ValidationException::withMessages([
                'proof' => __(filled($user->password)
                    ? 'account_deletion.errors.password_wrong'
                    : 'account_deletion.errors.email_wrong'),
            ]);
        }
    }

    private function shouldPause(Biodata $biodata): bool
    {
        return match ($biodata->status) {
            BiodataStatus::Approved, BiodataStatus::Pending => true,
            BiodataStatus::Hidden => $biodata->hidden_source === HiddenSource::Owner,
            default => false,
        };
    }
}
