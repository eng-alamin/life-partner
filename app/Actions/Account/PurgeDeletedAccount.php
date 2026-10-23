<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\BiodataStatus;
use App\Models\Biodata;
use App\Models\User;
use App\Services\Identity\IdentityFileStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Purges ONE account whose deletion request has waited out its period.
 *
 * What is deleted: the biodata's parts (education, preference, guardian, photos and their
 * files), the member's own lists (shortlist, ignore list, blocks, saved searches), codes,
 * social links, SMS records, notifications, sessions, roles, and the payer number and
 * screenshot of the member's payments.
 *
 * What is kept: payments, payment events, subscriptions, the credit ledger, contact unlocks,
 * interests (status and dates only) and reports. These rows point to the user and the biodata
 * with cascading keys, so NEITHER row is ever hard-deleted:
 *  - the user row is anonymised and soft deleted (login is impossible);
 *  - the biodata row stays, with every personal column emptied, and is never searchable.
 *
 * The request is re-checked under a row lock, so a member who cancelled a moment ago is safe.
 * The purge is paused (and tried again on the next run) while a report against the member's
 * biodata is open or a payment is unfinished: staff decide those first.
 * The activity log gets the action name only.
 */
final class PurgeDeletedAccount
{
    public const PURGED = 'purged';

    public const SKIPPED = 'skipped';

    public const PAUSED_REPORT = 'paused_report';

    public const PAUSED_PAYMENT = 'paused_payment';

    /** @return self::PURGED|self::SKIPPED|self::PAUSED_REPORT|self::PAUSED_PAYMENT */
    public function __invoke(int $userId): string
    {
        $photoFiles = [];
        $proofFiles = [];
        $identityFiles = [];

        $result = DB::transaction(function () use ($userId, &$photoFiles, &$proofFiles, &$identityFiles): string {
            $user = User::query()->whereKey($userId)->lockForUpdate()->first();

            if ($user === null
                || $user->deletion_requested_at === null
                || $user->deletion_scheduled_for === null
                || $user->deletion_scheduled_for->isFuture()
                || $user->roles()->exists()) {
                return self::SKIPPED;
            }

            $biodata = Biodata::query()->where('user_id', $userId)->lockForUpdate()->first();

            if ($biodata !== null && DB::table('reports')
                ->where('biodata_id', $biodata->getKey())
                ->where('status', 'open')
                ->exists()) {
                return self::PAUSED_REPORT;
            }

            if (DB::table('payments')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'under_review'])
                ->exists()) {
                return self::PAUSED_PAYMENT;
            }

            if ($biodata !== null) {
                $photoFiles = $this->purgeBiodata($biodata);
            }

            $proofFiles = $this->purgePayments($userId);
            $identityFiles = $this->purgeIdentity($userId);
            $this->purgeMemberData($user);
            $this->anonymise($user);

            activity('account')
                ->performedOn($user)
                ->withProperties(['action' => 'purged'])
                ->log('account.purged');

            return self::PURGED;
        });

        // Files go only after the database commit, so a rollback never loses a file.
        if ($photoFiles !== []) {
            Storage::disk('photos')->delete($photoFiles);
        }

        if ($proofFiles !== []) {
            Storage::disk('payment_proofs')->delete($proofFiles);
        }

        if ($identityFiles !== []) {
            app(IdentityFileStore::class)->delete(...$identityFiles);
        }

        return $result;
    }

    /**
     * Identity requests hold no document number, but they are personal. The rows and any waiting
     * pictures go. (The user row is only soft deleted, so nothing cascades by itself.)
     *
     * @return list<string> picture files to delete after commit
     */
    private function purgeIdentity(int $userId): array
    {
        $files = [];

        foreach (DB::table('identity_verifications')->where('user_id', $userId)->get(['front_path', 'back_path']) as $row) {
            foreach ([$row->front_path, $row->back_path] as $path) {
                if ($path !== null && $path !== '') {
                    $files[] = (string) $path;
                }
            }
        }

        DB::table('identity_verifications')->where('user_id', $userId)->delete();

        return $files;
    }

    /** @return list<string> photo files to delete after commit */
    private function purgeBiodata(Biodata $biodata): array
    {
        $id = $biodata->getKey();

        $files = [];
        foreach (DB::table('biodata_photos')->where('biodata_id', $id)->get(['path', 'thumb_path']) as $photo) {
            $files[] = (string) $photo->path;
            $files[] = (string) $photo->thumb_path;
        }

        DB::table('biodata_photos')->where('biodata_id', $id)->delete();
        DB::table('biodata_educations')->where('biodata_id', $id)->delete();
        DB::table('biodata_preferences')->where('biodata_id', $id)->delete();
        DB::table('guardians')->where('biodata_id', $id)->delete();

        // Other members' lists that point at this biodata.
        DB::table('shortlists')->where('biodata_id', $id)->delete();
        DB::table('ignored_biodatas')->where('biodata_id', $id)->delete();

        // The other side keeps status and dates only: no message, no guardian link.
        DB::table('interests')
            ->where(function ($query) use ($id): void {
                $query->where('sender_biodata_id', $id)->orWhere('receiver_biodata_id', $id);
            })
            ->update(['message' => null, 'guardian_token_hash' => null, 'guardian_token_expires_at' => null]);

        $blank = [];
        foreach ($biodata->getFillable() as $column) {
            $blank[$column] = null;
        }

        $biodata->forceFill($blank + [
            'status' => BiodataStatus::Deactivated,
            'rejection_reason' => null,
            'completion_pct' => 0,
            'highest_education_rank' => null,
            'photo_visibility' => 'hidden',
            'identity_verified_at' => null,
            'hidden_at' => null,
            'hidden_source' => null,
            'hidden_from' => null,
            'hidden_revision' => null,
            'hidden_by' => null,
            'deactivated_at' => $biodata->deactivated_at ?? now(),
            'deactivation_reason' => $biodata->deactivation_reason ?? 'account_deleted',
        ])->save();

        return array_values(array_filter($files, fn (string $path): bool => $path !== ''));
    }

    /** @return list<string> screenshot files to delete after commit */
    private function purgePayments(int $userId): array
    {
        $files = DB::table('payments')
            ->where('user_id', $userId)
            ->whereNotNull('proof_path')
            ->pluck('proof_path')
            ->map(fn ($path): string => (string) $path)
            ->filter(fn (string $path): bool => $path !== '')
            ->values()
            ->all();

        DB::table('payments')
            ->where('user_id', $userId)
            ->update(['payer_number' => null, 'proof_path' => null]);

        return $files;
    }

    private function purgeMemberData(User $user): void
    {
        $id = $user->getKey();

        DB::table('otp_codes')->where('user_id', $id)->delete();
        DB::table('social_accounts')->where('user_id', $id)->delete();
        DB::table('saved_searches')->where('user_id', $id)->delete();
        DB::table('shortlists')->where('user_id', $id)->delete();
        DB::table('ignored_biodatas')->where('user_id', $id)->delete();
        DB::table('notification_preferences')->where('user_id', $id)->delete();
        DB::table('sms_messages')->where('user_id', $id)->delete();
        DB::table('sessions')->where('user_id', $id)->delete();

        DB::table('blocks')
            ->where('blocker_user_id', $id)
            ->orWhere('blocked_user_id', $id)
            ->delete();

        DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $id)
            ->delete();

        $user->syncRoles([]);
        $user->syncPermissions([]);
    }

    private function anonymise(User $user): void
    {
        $user->forceFill([
            'name' => 'Deleted member',
            'email' => 'deleted-'.$user->getKey().'@deleted.invalid',
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'phone' => null,
            'phone_hash' => null,
            'phone_verified_at' => null,
            'last_login_at' => null,
        ])->save();

        $user->delete();
    }
}
