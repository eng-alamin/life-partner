<?php

declare(strict_types=1);

namespace App\Services\Biodata;

use App\Enums\BiodataStatus;
use App\Enums\DeactivationReason;
use App\Enums\HiddenSource;
use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;
use App\Services\Interest\InterestTransitions;

/**
 * The only place that moves a biodata into and out of the hidden status.
 *
 * Callers must already be inside a database transaction, hold a row lock on the
 * biodata and have checked authorization. This class does no authorization itself.
 */
final class BiodataHiding
{
    public function __construct(private readonly InterestTransitions $transitions) {}

    /** Hides an approved or pending biodata and cancels its pending interests (actor: system). */
    public function hide(Biodata $biodata, HiddenSource $source, User $by): void
    {
        $from = $biodata->status;

        $biodata->forceFill([
            'status' => BiodataStatus::Hidden->value,
            'hidden_at' => now(),
            'hidden_source' => $source->value,
            'hidden_from' => $from->value,
            'hidden_revision' => (int) $biodata->revision,
            'hidden_by' => $by->getKey(),
        ])->save();

        $this->cancelPendingInterests($biodata, $by);
    }

    /**
     * Pauses a biodata for good reasons of the member's own (status deactivated) and cancels its
     * pending interests (actor: system). Allowed from approved, pending or a biodata the owner hid.
     * The place to return to (hidden_from and hidden_revision) is kept from the earlier hide, or
     * recorded now, so reactivating follows the same rule as showing a hidden biodata.
     */
    public function deactivate(Biodata $biodata, User $by, ?DeactivationReason $reason): void
    {
        $attributes = [
            'status' => BiodataStatus::Deactivated->value,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason?->value,
        ];

        if ($biodata->status !== BiodataStatus::Hidden) {
            $attributes += [
                'hidden_at' => now(),
                'hidden_source' => HiddenSource::Owner->value,
                'hidden_from' => $biodata->status->value,
                'hidden_revision' => (int) $biodata->revision,
                'hidden_by' => $by->getKey(),
            ];
        }

        $biodata->forceFill($attributes)->save();

        $this->cancelPendingInterests($biodata, $by);
    }

    /**
     * Brings a hidden or deactivated biodata back. It returns to approved only if it was approved when hidden
     * AND its content has not changed since. In every other case it goes to review again.
     */
    public function restore(Biodata $biodata): BiodataStatus
    {
        $unchanged = $biodata->hidden_from === BiodataStatus::Approved->value
            && $biodata->hidden_revision !== null
            && (int) $biodata->hidden_revision === (int) $biodata->revision;

        $to = $unchanged ? BiodataStatus::Approved : BiodataStatus::Pending;

        $attributes = [
            'status' => $to->value,
            'hidden_at' => null,
            'hidden_source' => null,
            'hidden_from' => null,
            'hidden_revision' => null,
            'hidden_by' => null,
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ];

        if ($to === BiodataStatus::Pending) {
            $attributes['submitted_at'] = now();
            $attributes['rejection_reason'] = null;
        }

        $biodata->forceFill($attributes)->save();

        return $to;
    }

    private function cancelPendingInterests(Biodata $biodata, User $by): void
    {
        $pending = Interest::query()
            ->where('status', InterestStatus::Pending->value)
            ->where(function ($query) use ($biodata): void {
                $query->where('sender_biodata_id', $biodata->getKey())
                    ->orWhere('receiver_biodata_id', $biodata->getKey());
            })
            ->lockForUpdate()
            ->get();

        foreach ($pending as $interest) {
            $this->transitions->move($interest, InterestStatus::Cancelled, InterestActor::System, $by);
        }
    }
}
