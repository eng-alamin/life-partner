<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Enums\NotificationType;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Biodata;
use App\Models\Report;
use App\Models\User;
use App\Services\Notification\Notifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Files a report about an approved biodata, found by its public code only.
 *
 * - One open report per member and biodata (also enforced by the unique open_key).
 * - Daily limit over a rolling 24 hours. The reporter's user row is locked while
 *   the rules run, so two quick taps cannot slip past the limit.
 * - The free text is stored but never written to the activity log; the log gets
 *   the public code and the reason only.
 * - The reported member is never told.
 */
final class SubmitReport
{
    public function __construct(private readonly Notifier $notifier) {}

    public function __invoke(User $user, string $code, ?string $reason, ?string $details = null): Report
    {
        try {
            $report = DB::transaction(fn (): Report => $this->submit($user, $code, $reason, $details));
        } catch (UniqueConstraintViolationException) {
            throw $this->error('already_reported');
        }

        $this->notifier->toStaff(NotificationType::StaffReportNew);

        return $report;
    }

    private function submit(User $user, string $code, ?string $reason, ?string $details): Report
    {
        User::query()->whereKey($user->getKey())->lockForUpdate()->first();

        $target = Biodata::query()->searchable()->where('public_code', $code)->first();

        if ($target === null) {
            throw $this->error('unavailable');
        }

        Gate::forUser($user)->authorize('create', [Report::class, $target]);

        $chosen = ReportReason::tryFrom((string) $reason);

        if ($chosen === null) {
            throw $this->error('reason_required', 'reason');
        }

        $text = $this->cleanDetails($chosen, $details);

        $this->assertNotAlreadyReported($user, $target);
        $this->assertWithinDailyLimit($user);

        $report = new Report;
        $report->forceFill([
            'reporter_user_id' => $user->getKey(),
            'biodata_id' => $target->getKey(),
            'reported_code' => $target->public_code,
            'reason' => $chosen->value,
            'details' => $text,
            'status' => ReportStatus::Open->value,
        ])->save();

        activity('report')
            ->causedBy($user)
            ->withProperties(['target_code' => $target->public_code, 'reason' => $chosen->value])
            ->log('report.created');

        return $report;
    }

    private function cleanDetails(ReportReason $reason, ?string $details): ?string
    {
        $text = trim((string) $details);
        $max = (int) config('lifepartner.reports.details_max');

        if (mb_strlen($text) > $max) {
            throw $this->error('details_too_long', 'details', ['max' => $max]);
        }

        if ($text === '') {
            if ($reason->needsDetails()) {
                throw $this->error('details_required', 'details');
            }

            return null;
        }

        return $text;
    }

    private function assertNotAlreadyReported(User $user, Biodata $target): void
    {
        $exists = Report::query()
            ->where('reporter_user_id', $user->getKey())
            ->where('biodata_id', $target->getKey())
            ->where('status', ReportStatus::Open->value)
            ->exists();

        if ($exists) {
            throw $this->error('already_reported');
        }
    }

    /** Rolling 24 hours, counted over every report the member filed, whatever its status now. */
    private function assertWithinDailyLimit(User $user): void
    {
        $limit = (int) config('lifepartner.reports.daily_limit');

        $filed = Report::query()
            ->where('reporter_user_id', $user->getKey())
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($filed >= $limit) {
            throw $this->error('daily_limit', 'form', ['limit' => $limit]);
        }
    }

    /** @param  array<string, int|string>  $replace */
    private function error(string $key, string $field = 'form', array $replace = []): ValidationException
    {
        return ValidationException::withMessages([$field => __('reports.errors.'.$key, $replace)]);
    }
}
