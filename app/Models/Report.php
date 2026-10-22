<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A member's report about a biodata. Nothing is mass-assignable: rows are
 * written only by SubmitReport (and, in Module 7.4, by the staff actions) with
 * forceFill(). The free text in `details` is hidden from serialization and is
 * never written to the activity log.
 */
class Report extends Model
{
    /** @var list<string> */
    protected $fillable = [];

    /** @var list<string> */
    protected $hidden = ['details', 'open_key'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'open',
    ];

    protected static function booted(): void
    {
        static::creating(function (Report $report): void {
            if (blank($report->code)) {
                $report->code = Str::lower(Str::random(24));
            }
        });

        // Keep the unique "one open report per member and biodata" key in step with the status.
        static::saving(function (Report $report): void {
            $report->open_key = $report->status === ReportStatus::Open
                ? $report->reporter_user_id.'-'.$report->biodata_id
                : null;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'reason' => ReportReason::class,
            'handled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function biodata(): BelongsTo
    {
        return $this->belongsTo(Biodata::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
