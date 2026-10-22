<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Enums\BiodataStatus;
use App\Enums\HiddenSource;
use App\Enums\ReportStatus;
use App\Models\Biodata;
use App\Models\Report;
use App\Models\User;
use App\Services\Biodata\BiodataHiding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Staff decision on an open report: dismiss, resolve, or resolve and hide the biodata.
 *
 * Every decision runs in a transaction on a freshly locked report and refuses
 * to act if the report is no longer open. Hiding also takes the biodata out of
 * search at once, cancels its pending interests (actor: system) and resolves the
 * other open reports about the same biodata with the same note.
 *
 * The activity log gets codes, statuses and counts only. It never gets the
 * reporter's text or the staff note.
 */
final class HandleReport
{
    private const NOTE_MIN = 10;

    private const NOTE_MAX = 500;

    public function __construct(private readonly BiodataHiding $hiding) {}

    /** No action on the biodata. The note is optional. */
    public function dismiss(User $staff, int $reportId, ?string $note = null): Report
    {
        return $this->decide($staff, $reportId, ReportStatus::Dismissed, $this->cleanNote($note, false), false);
    }

    /** The problem was dealt with in some other way (for example a talk with the member). */
    public function resolve(User $staff, int $reportId, ?string $note): Report
    {
        return $this->decide($staff, $reportId, ReportStatus::Resolved, $this->cleanNote($note, true), false);
    }

    /** Hides the reported biodata and resolves this report. */
    public function hideBiodata(User $staff, int $reportId, ?string $note): Report
    {
        return $this->decide($staff, $reportId, ReportStatus::Resolved, $this->cleanNote($note, true), true);
    }

    private function decide(User $staff, int $reportId, ReportStatus $to, ?string $note, bool $hide): Report
    {
        return DB::transaction(function () use ($staff, $reportId, $to, $note, $hide): Report {
            $report = Report::query()->lockForUpdate()->findOrFail($reportId);

            Gate::forUser($staff)->authorize('handle', $report);

            if ($report->status !== ReportStatus::Open) {
                throw $this->error('not_open');
            }

            $hidden = null;
            $alsoResolved = 0;

            if ($hide) {
                $hidden = $this->hide($staff, $report);
                $alsoResolved = $this->closeOtherReports($staff, $report, $note);
            }

            $this->close($report, $staff, $to, $note);

            $properties = [
                'report_code' => $report->code,
                'reported_code' => $report->reported_code,
                'from' => ReportStatus::Open->value,
                'to' => $to->value,
            ];

            if ($hidden !== null) {
                $properties['biodata_from'] = $hidden->value;
                $properties['biodata_to'] = BiodataStatus::Hidden->value;
                $properties['also_resolved'] = $alsoResolved;
            }

            activity('moderation')
                ->causedBy($staff)
                ->performedOn($report)
                ->event($hide ? 'hidden' : $to->value)
                ->withProperties($properties)
                ->log($hide ? 'report.hidden' : 'report.'.$to->value);

            return $report;
        });
    }

    /**
     * @return BiodataStatus the status the biodata had before it was hidden
     */
    private function hide(User $staff, Report $report): BiodataStatus
    {
        $biodata = $report->biodata_id === null
            ? null
            : Biodata::query()->lockForUpdate()->find($report->biodata_id);

        if ($biodata === null || ! in_array($biodata->status, [BiodataStatus::Approved, BiodataStatus::Pending], true)) {
            throw $this->error('biodata_unavailable');
        }

        $from = $biodata->status;

        // Records that STAFF hid it, so the member can not simply show it again.
        $this->hiding->hide($biodata, HiddenSource::Staff, $staff);

        return $from;
    }

    /** @return int how many other open reports about the same biodata were closed */
    private function closeOtherReports(User $staff, Report $report, ?string $note): int
    {
        $others = Report::query()
            ->where('biodata_id', $report->biodata_id)
            ->where('status', ReportStatus::Open->value)
            ->whereKeyNot($report->getKey())
            ->lockForUpdate()
            ->get();

        foreach ($others as $other) {
            $this->close($other, $staff, ReportStatus::Resolved, $note);
        }

        return $others->count();
    }

    private function close(Report $report, User $staff, ReportStatus $to, ?string $note): void
    {
        $report->forceFill([
            'status' => $to->value,
            'handled_by' => $staff->getKey(),
            'handled_at' => now(),
            'resolution' => $note,
        ])->save();
    }

    /** Removes control characters (keeps line breaks), trims, and checks the length. */
    private function cleanNote(?string $note, bool $required): ?string
    {
        $text = trim((string) preg_replace('/[^\P{C}\n]/u', '', (string) $note));

        if ($text === '') {
            if ($required) {
                throw $this->error('resolution_required', 'resolution');
            }

            return null;
        }

        if ($required && mb_strlen($text) < self::NOTE_MIN) {
            throw $this->error('resolution_required', 'resolution');
        }

        if (mb_strlen($text) > self::NOTE_MAX) {
            throw $this->error('resolution_too_long', 'resolution');
        }

        return $text;
    }

    private function error(string $key, string $field = 'form'): ValidationException
    {
        return ValidationException::withMessages([$field => __('admin_reports.errors.'.$key)]);
    }
}
