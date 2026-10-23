<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Enums\IdentityRejectionReason;
use App\Enums\IdentityStatus;
use App\Enums\NotificationType;
use App\Models\IdentityVerification;
use App\Services\Identity\IdentityFileStore;
use App\Services\Notification\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Nightly cleanup of identity requests. It does two things:
 *
 * 1. A request that waited longer than lifepartner.identity.expire_days (30) is closed as
 *    "rejected: timed out", its pictures are deleted, and the member is told. The member may send a new one.
 * 2. A picture file that no waiting request points to, and is more than a day old, is deleted. (A failed
 *    delete after a decision, or a failed upload, can leave such a file. A day's grace protects a picture
 *    that is being uploaded right now.)
 *
 * The activity log gets request codes and counts only.
 */
final class CleanIdentityRequests
{
    private const LEFTOVER_GRACE_HOURS = 24;

    public function __construct(
        private readonly IdentityFileStore $files,
        private readonly Notifier $notifier,
    ) {}

    /** @return array{expired: int, files: int} */
    public function __invoke(): array
    {
        return ['expired' => $this->closeOldRequests(), 'files' => $this->deleteLeftoverFiles()];
    }

    private function closeOldRequests(): int
    {
        $days = max(1, (int) config('lifepartner.identity.expire_days'));

        $codes = IdentityVerification::query()
            ->where('status', IdentityStatus::Pending->value)
            ->where('submitted_at', '<', now()->subDays($days))
            ->orderBy('id')
            ->limit(500)
            ->pluck('code');

        $closed = 0;

        foreach ($codes as $code) {
            try {
                $closed += $this->close((string) $code) ? 1 : 0;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $closed;
    }

    private function close(string $code): bool
    {
        $paths = [];
        $userId = null;

        $done = DB::transaction(function () use ($code, &$paths, &$userId): bool {
            $verification = IdentityVerification::query()->where('code', $code)->lockForUpdate()->first();

            // Decided by staff a moment ago: leave it.
            if ($verification === null || ! $verification->isPending()) {
                return false;
            }

            $paths = [$verification->front_path, $verification->back_path];
            $userId = (int) $verification->user_id;

            $verification->forceFill([
                'status' => IdentityStatus::Rejected->value,
                'rejection_reason' => IdentityRejectionReason::TimedOut->value,
                'open_key' => null,
                'front_path' => null,
                'back_path' => null,
                'files_deleted_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => null,
            ])->save();

            activity('identity')
                ->withProperties(['identity_code' => $verification->code])
                ->log('identity.expired');

            return true;
        });

        if (! $done) {
            return false;
        }

        $this->files->delete(...$paths);

        if ($userId !== null) {
            $this->notifier->toUser($userId, NotificationType::IdentityRejected);
        }

        return true;
    }

    private function deleteLeftoverFiles(): int
    {
        $disk = Storage::disk(IdentityFileStore::DISK);

        $kept = [];
        IdentityVerification::query()
            ->where('status', IdentityStatus::Pending->value)
            ->get(['id', 'front_path', 'back_path'])
            ->each(function (IdentityVerification $row) use (&$kept): void {
                foreach ([$row->front_path, $row->back_path] as $path) {
                    if ($path !== null && $path !== '') {
                        $kept[(string) $path] = true;
                    }
                }
            });

        $limit = now()->subHours(self::LEFTOVER_GRACE_HOURS)->getTimestamp();
        $deleted = 0;

        try {
            $all = $disk->allFiles();
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }

        foreach ($all as $path) {
            if (isset($kept[$path])) {
                continue;
            }

            try {
                if ($disk->lastModified($path) > $limit) {
                    continue;
                }

                $disk->delete($path);
                $deleted++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($deleted > 0) {
            activity('identity')
                ->withProperties(['deleted' => $deleted])
                ->log('identity.files_cleaned');
        }

        return $deleted;
    }
}
