<?php

declare(strict_types=1);

namespace App\Services\Biodata;

use App\Models\Biodata;

/**
 * Knows which biodata steps are filled in. Used to pick the step a member
 * lands on, to stop them jumping ahead, and to compute completion_pct.
 */
final class BiodataProgress
{
    /** Every step of the finished form, in order. */
    public const ALL_STEPS = [
        'general', 'address', 'education', 'work', 'religion',
        'family', 'personal', 'marriage', 'preferences', 'guardian',
    ];

    public function isStepComplete(?Biodata $biodata, string $step): bool
    {
        if ($biodata === null || ! $biodata->exists) {
            return false;
        }

        $biodata->loadMissing(['educations', 'preference', 'guardian']);

        return match ($step) {
            'general' => $this->filled($biodata, ['type', 'marital_status', 'birth_date', 'height_cm']),
            'address' => $this->addressComplete($biodata),
            'education' => $biodata->educations->isNotEmpty()
                && $biodata->educations->every(fn ($row): bool => $row->level !== null),
            'work' => $this->filled($biodata, ['profession', 'income_range']),
            'religion' => $this->filled($biodata, ['prayer_habit', 'quran_reading', 'madhhab', 'practicing_level', 'dress_practice']),
            'family' => $this->filled($biodata, ['father_status', 'mother_status', 'brothers_count', 'sisters_count', 'family_financial_status']),
            'personal' => filled($biodata->about_me),
            'marriage' => $this->filled($biodata, ['marriage_timeline', 'spouse_work_after_marriage', 'living_arrangement']),
            'preferences' => $biodata->preference !== null
                && $biodata->preference->age_min !== null
                && $biodata->preference->age_max !== null,
            'guardian' => $biodata->guardian !== null,
            default => false,
        };
    }

    /**
     * 1-based position of the first unfinished step inside $steps,
     * or null when every step in $steps is finished.
     *
     * @param  list<string>  $steps
     */
    public function firstIncomplete(?Biodata $biodata, array $steps): ?int
    {
        foreach ($steps as $index => $step) {
            if (! $this->isStepComplete($biodata, $step)) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * Furthest step (1-based) a member may open: the first unfinished one,
     * or the last step when everything is finished.
     *
     * @param  list<string>  $steps
     */
    public function reachable(?Biodata $biodata, array $steps): int
    {
        return $this->firstIncomplete($biodata, $steps) ?? count($steps);
    }

    /** Percent of ALL_STEPS that are finished (0 to 100). */
    public function percent(Biodata $biodata): int
    {
        $done = 0;

        foreach (self::ALL_STEPS as $step) {
            if ($this->isStepComplete($biodata, $step)) {
                $done++;
            }
        }

        return (int) round($done / count(self::ALL_STEPS) * 100);
    }

    private function addressComplete(Biodata $biodata): bool
    {
        if (blank($biodata->present_country) || $biodata->permanent_district_id === null) {
            return false;
        }

        return $biodata->present_country === 'BD'
            ? $biodata->present_district_id !== null
            : filled($biodata->present_city);
    }

    /** @param list<string> $attributes */
    private function filled(Biodata $biodata, array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if (blank($biodata->getAttribute($attribute)) && $biodata->getAttribute($attribute) !== 0) {
                return false;
            }
        }

        return true;
    }
}
