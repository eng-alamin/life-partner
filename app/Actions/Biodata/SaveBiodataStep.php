<?php

declare(strict_types=1);

namespace App\Actions\Biodata;

use App\Enums\BiodataStatus;
use App\Enums\EducationLevel;
use App\Enums\NotificationType;
use App\Models\Biodata;
use App\Models\BiodataPreference;
use App\Models\Guardian;
use App\Models\User;
use App\Services\Biodata\BiodataProgress;
use App\Services\Notification\Notifier;
use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use LogicException;

/**
 * Saves one already-validated step of the biodata form.
 *
 * - Creates the draft biodata on the first save (never on a plain page view).
 * - Authorizes through BiodataPolicy (create / update).
 * - If a real change is made to an APPROVED biodata it goes back to review, and staff is told once.
 * - A real change to a PENDING biodata keeps it pending; every real change raises `revision`.
 */
final class SaveBiodataStep
{
    /** Columns each simple step may write. Anything else in $data is ignored. */
    private const FIELDS = [
        'general' => ['type', 'marital_status', 'birth_date', 'height_cm', 'weight_kg', 'complexion', 'blood_group'],
        'address' => ['present_country', 'present_district_id', 'present_city', 'permanent_district_id'],
        'work' => ['profession', 'employer', 'income_range'],
        'religion' => ['prayer_habit', 'quran_reading', 'madhhab', 'practicing_level', 'dress_practice'],
        'family' => ['father_status', 'mother_status', 'brothers_count', 'sisters_count', 'family_financial_status'],
        'personal' => ['hobbies', 'about_me'],
        'marriage' => ['marriage_timeline', 'spouse_work_after_marriage', 'living_arrangement'],
    ];

    private const PREFERENCES = [
        'age_min', 'age_max', 'height_min_cm', 'min_education_level',
        'preferred_country', 'preferred_district_id', 'marital_statuses', 'min_practicing_level',
    ];

    private const INTEGERS = [
        'height_cm', 'weight_kg', 'present_district_id', 'permanent_district_id',
        'brothers_count', 'sisters_count', 'age_min', 'age_max', 'height_min_cm',
    ];

    public function __construct(
        private readonly BiodataProgress $progress,
        private readonly Notifier $notifier,
    ) {}

    /** @param array<string, mixed> $data validated input for this step */
    public function __invoke(User $user, string $step, array $data): Biodata
    {
        if (! in_array($step, ['education', 'preferences', 'guardian'], true) && ! array_key_exists($step, self::FIELDS)) {
            throw new InvalidArgumentException("Unknown biodata step [{$step}].");
        }

        $sentBack = false;

        $saved = DB::transaction(function () use ($user, $step, $data, &$sentBack): Biodata {
            $biodata = Biodata::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            if ($biodata === null) {
                if ($step !== 'general') {
                    throw new LogicException('The general step must be saved first.');
                }

                Gate::forUser($user)->authorize('create', Biodata::class);

                $biodata = new Biodata;
                $biodata->user_id = $user->getKey();
            } else {
                Gate::forUser($user)->authorize('update', $biodata);
            }

            $changed = match ($step) {
                'education' => $this->saveEducation($biodata, $data),
                'preferences' => $this->savePreferences($biodata, $data),
                'guardian' => $this->saveGuardian($biodata, $data),
                default => $this->saveFields($biodata, $step, $data),
            };

            if ($changed && $biodata->exists) {
                // Lets a reviewer's approve/reject detect that the content moved on.
                $biodata->forceFill(['revision' => (int) $biodata->revision + 1]);
            }

            if ($changed && $biodata->status === BiodataStatus::Approved) {
                $biodata->forceFill([
                    'status' => BiodataStatus::Pending->value,
                    'submitted_at' => now(),
                    'rejection_reason' => null,
                ]);
                $sentBack = true;
            }

            $biodata->save();

            $biodata->unsetRelations();
            $biodata->forceFill(['completion_pct' => $this->progress->percent($biodata)])->save();

            return $biodata;
        });

        // Once per trip from approved back to review: later edits find it already pending.
        // Sent after the transaction, so a rolled-back save tells nobody.
        if ($sentBack) {
            $this->notifier->toStaff(NotificationType::StaffBiodataChanged);
        }

        return $saved;
    }

    /** @param array<string, mixed> $data */
    private function saveFields(Biodata $biodata, string $step, array $data): bool
    {
        $fields = $this->clean(Arr::only($data, self::FIELDS[$step]));

        if ($step === 'general' && $biodata->exists && $biodata->submitted_at !== null) {
            // The type (groom or bride) cannot change once the biodata was ever submitted.
            unset($fields['type']);
        }

        if ($step === 'address' && ($fields['present_country'] ?? null) !== 'BD') {
            // A member living abroad has no Bangladeshi present district.
            $fields['present_district_id'] = null;
        }

        $biodata->fill($fields);

        // The badge vouches for the birth date and the type. If either changes, it is gone.
        if ($biodata->exists && $biodata->identity_verified_at !== null && $biodata->isDirty(['birth_date', 'type'])) {
            $biodata->forceFill(['identity_verified_at' => null]);

            activity('identity')
                ->causedBy((int) $biodata->user_id)
                ->performedOn($biodata)
                ->withProperties(['biodata_code' => $biodata->public_code])
                ->log('identity.badge_removed');
        }

        return $biodata->isDirty() || ! $biodata->exists;
    }

    /** @param array<string, mixed> $data */
    private function saveEducation(Biodata $biodata, array $data): bool
    {
        $rows = [];

        foreach (array_values((array) ($data['educations'] ?? [])) as $index => $row) {
            $rows[] = [
                'degree' => (string) $row['degree'],
                'level' => (string) $row['level'],
                'institution' => (string) $row['institution'],
                'passing_year' => (int) $row['passing_year'],
                'sort_order' => $index,
            ];
        }

        $existing = $biodata->educations()->get()
            ->map(fn ($e): array => [
                'degree' => $e->degree,
                'level' => $e->level?->value,
                'institution' => $e->institution,
                'passing_year' => (int) $e->passing_year,
                'sort_order' => (int) $e->sort_order,
            ])
            ->all();

        // Kept in step with the rows so the "minimum education" filter can use one indexed column.
        $biodata->forceFill(['highest_education_rank' => $this->highestRank($rows)]);

        if ($existing === $rows) {
            return false;
        }

        $biodata->educations()->delete();

        foreach ($rows as $row) {
            $biodata->educations()->create($row);
        }

        return true;
    }

    /** @param list<array{level: string}> $rows */
    private function highestRank(array $rows): ?int
    {
        $ranks = [];

        foreach ($rows as $row) {
            $level = EducationLevel::tryFrom($row['level']);

            if ($level !== null) {
                $ranks[] = $level->rank();
            }
        }

        return $ranks === [] ? null : max($ranks);
    }

    /**
     * Partner preferences are shown on the profile, so a real change to an
     * approved biodata sends it back to review.
     *
     * @param  array<string, mixed>  $data
     */
    private function savePreferences(Biodata $biodata, array $data): bool
    {
        $fields = $this->clean(Arr::only($data, self::PREFERENCES));

        if (($fields['preferred_country'] ?? null) !== 'BD') {
            $fields['preferred_district_id'] = null;
        }

        if (empty($fields['marital_statuses'])) {
            $fields['marital_statuses'] = null;
        }

        $preference = $biodata->preference()->first();
        $isNew = $preference === null;
        $preference ??= new BiodataPreference;
        $before = $isNew ? [] : $this->snapshot($preference);

        $preference->biodata_id = $biodata->getKey();
        $preference->fill($fields);

        $changed = $isNew || $before !== $this->snapshot($preference);

        if ($changed) {
            $preference->save();
        }

        return $changed;
    }

    /**
     * Guardian details are private and never shown to other members, so
     * changing them does not send an approved biodata back to review.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveGuardian(Biodata $biodata, array $data): bool
    {
        $guardian = $biodata->guardian()->first() ?? new Guardian;

        $guardian->biodata_id = $biodata->getKey();
        $guardian->fill([
            'name' => (string) $data['guardian_name'],
            'relation' => (string) $data['guardian_relation'],
            'phone' => (string) $data['guardian_phone'],
        ]);
        $guardian->can_approve = (bool) ($data['guardian_can_approve'] ?? true);
        $guardian->save();

        return false;
    }

    /** @return array<string, mixed> */
    private function snapshot(BiodataPreference $preference): array
    {
        $out = [];

        foreach (self::PREFERENCES as $key) {
            $value = $preference->getAttribute($key);

            if ($value instanceof Collection) {
                $value = $value->map(fn ($e) => $e instanceof BackedEnum ? $e->value : $e)->sort()->values()->all();
            } elseif ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            // Compare numbers and strings alike (a database may return "25" or 25).
            $out[$key] = is_scalar($value) ? (string) $value : $value;
        }

        return $out;
    }

    /**
     * Blank strings become null and integer columns are cast.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function clean(array $fields): array
    {
        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '' || $value === null) {
                $fields[$key] = null;

                continue;
            }

            $fields[$key] = in_array($key, self::INTEGERS, true) ? (int) $value : $value;
        }

        return $fields;
    }
}
