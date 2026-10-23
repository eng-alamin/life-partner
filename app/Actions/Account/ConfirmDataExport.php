<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use App\Support\Digits;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Step one of "download my data": the member proves who they are and gets a one-time,
 * short-lived permission (kept in their own session) to open the download link once.
 *
 * - The account is always the signed-in user, never an id sent by the browser.
 * - Proof is the password, or, for an account with no password (social sign-in), the
 *   account's own email address typed in full. The proof is never stored or logged.
 * - At most DAILY_LIMIT downloads per member per day (each finished download counts).
 */
final class ConfirmDataExport
{
    public const DAILY_LIMIT = 3;

    public const GRANT_SECONDS = 120;

    public const SESSION_KEY = 'account_export_grant';

    public static function limitKey(User $user): string
    {
        return 'account-export-download:'.$user->getKey();
    }

    public function __invoke(User $user, string $proof): void
    {
        if (RateLimiter::tooManyAttempts(self::limitKey($user), self::DAILY_LIMIT)) {
            throw ValidationException::withMessages([
                'form' => __('account_export.errors.limit', ['max' => Digits::localize(self::DAILY_LIMIT)]),
            ]);
        }

        $this->confirmIdentity($user, $proof);

        session()->put(self::SESSION_KEY, [
            'user' => (int) $user->getKey(),
            'until' => now()->addSeconds(self::GRANT_SECONDS)->getTimestamp(),
        ]);
    }

    /** Uses up the permission. It works once, only for the same member, and only for a short time. */
    public static function consumeGrant(User $user): bool
    {
        $grant = session()->pull(self::SESSION_KEY);

        return is_array($grant)
            && (int) ($grant['user'] ?? 0) === (int) $user->getKey()
            && (int) ($grant['until'] ?? 0) >= now()->getTimestamp();
    }

    private function confirmIdentity(User $user, string $proof): void
    {
        $ok = filled($user->password)
            ? Hash::check($proof, (string) $user->password)
            : mb_strtolower(trim($proof)) === mb_strtolower((string) $user->email);

        if (! $ok) {
            throw ValidationException::withMessages([
                'proof' => __(filled($user->password)
                    ? 'account_export.errors.password_wrong'
                    : 'account_export.errors.email_wrong'),
            ]);
        }
    }
}
