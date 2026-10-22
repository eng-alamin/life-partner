<?php

declare(strict_types=1);

namespace App\Livewire\Biodata;

use App\Actions\Biodata\SaveBiodataStep;
use App\Actions\Biodata\SubmitBiodata;
use App\Enums\BiodataStatus;
use App\Enums\BiodataType;
use App\Enums\BloodGroup;
use App\Enums\Complexion;
use App\Enums\DressPractice;
use App\Enums\EducationLevel;
use App\Enums\FamilyFinancialStatus;
use App\Enums\GuardianRelation;
use App\Enums\IncomeRange;
use App\Enums\LivingArrangement;
use App\Enums\Madhhab;
use App\Enums\MaritalStatus;
use App\Enums\MarriageTimeline;
use App\Enums\ParentStatus;
use App\Enums\PracticingLevel;
use App\Enums\PrayerHabit;
use App\Enums\QuranReading;
use App\Enums\SpouseWorkAfterMarriage;
use App\Models\Biodata;
use App\Models\District;
use App\Rules\NoContactDetails;
use App\Services\Biodata\BiodataProgress;
use App\Support\Countries;
use App\Support\Digits;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Multi-step biodata form. There is no biodata id in the URL: the form always
 * works on the signed-in member's own biodata, so there is nothing to tamper with.
 * Every public property below is untrusted input and is validated on the server.
 */
#[Layout('layouts::app')]
class BiodataWizard extends Component
{
    public const STEPS = [
        'general', 'address', 'education', 'work', 'religion',
        'family', 'personal', 'marriage', 'preferences', 'guardian',
    ];

    private const MAX_EDUCATIONS = 5;

    /** Letters (any language), marks, digits, space and a few safe symbols. */
    private const TEXT_PATTERN = '/^[\p{L}\p{M}\p{N} .,\x27\x{2019}()&\/-]+$/u';

    public int $step = 1;

    // Step 1: general
    public string $type = '';

    public string $marital_status = '';

    public string $birth_date = '';

    public string $height_cm = '';

    public string $weight_kg = '';

    public string $complexion = '';

    public string $blood_group = '';

    // Step 2: address
    public string $present_country = Countries::HOME;

    public string $present_district_id = '';

    public string $present_city = '';

    public string $permanent_district_id = '';

    // Step 3: education
    /** @var list<array{degree: string, level: string, institution: string, passing_year: string}> */
    public array $educations = [];

    // Step 4: work
    public string $profession = '';

    public string $employer = '';

    public string $income_range = '';

    // Step 5: religion
    public string $prayer_habit = '';

    public string $quran_reading = '';

    public string $madhhab = '';

    public string $practicing_level = '';

    public string $dress_practice = '';

    // Step 6: family
    public string $father_status = '';

    public string $mother_status = '';

    public string $brothers_count = '';

    public string $sisters_count = '';

    public string $family_financial_status = '';

    // Step 7: personal
    public string $hobbies = '';

    public string $about_me = '';

    // Step 8: marriage
    public string $marriage_timeline = '';

    public string $spouse_work_after_marriage = '';

    public string $living_arrangement = '';

    // Step 9: partner preferences
    public string $age_min = '';

    public string $age_max = '';

    public string $height_min_cm = '';

    public string $min_education_level = '';

    public string $preferred_country = '';

    public string $preferred_district_id = '';

    /** @var list<string> */
    public array $marital_statuses = [];

    public string $min_practicing_level = '';

    // Step 10: guardian (private)
    public string $guardian_name = '';

    public string $guardian_relation = '';

    public string $guardian_phone = '';

    public bool $guardian_can_approve = true;

    /** Short message shown above the form after a save. */
    public string $notice = '';

    #[Locked]
    public bool $typeLocked = false;

    #[Locked]
    public bool $readOnly = false;

    #[Locked]
    public string $statusValue = '';

    #[Locked]
    public string $rejectionReason = '';

    public function mount(BiodataProgress $progress): void
    {
        $biodata = $this->currentBiodata();

        if ($biodata === null) {
            Gate::authorize('create', Biodata::class);
            $this->educations = [$this->blankEducation()];

            return;
        }

        Gate::authorize('view', $biodata);

        $this->fillFrom($biodata);
        $this->syncState($biodata);
        $this->step = $progress->firstIncomplete($biodata, self::STEPS) ?? 1;
    }

    /** Validates the current step, saves it, then moves on. */
    public function next(SaveBiodataStep $save, BiodataProgress $progress): void
    {
        $this->notice = '';
        $this->throttle();

        $this->step = $this->clampStep($progress);
        $key = self::STEPS[$this->step - 1];

        $this->normalize();

        $data = $this->validate($this->rulesFor($key), [], $this->attributeNames());

        $before = $this->currentBiodata()?->status;
        $saved = $save(auth()->user(), $key, $data);

        $this->syncState($saved);

        if ($before === BiodataStatus::Approved && $saved->status === BiodataStatus::Pending) {
            $this->notice = __('biodata.notice.sent_back_to_review');

            return;
        }

        if ($this->step < count(self::STEPS)) {
            $this->step++;

            return;
        }

        if ($this->canSubmit($saved, $progress)) {
            $this->notice = __('biodata.notice.ready_to_submit');

            return;
        }

        session()->flash('status', __('biodata.saved'));
        $this->redirectRoute('my-biodata');
    }

    /** Sends the finished biodata to the admin approval queue. */
    public function submit(SubmitBiodata $submit): void
    {
        $this->notice = '';
        $this->throttle();

        $submit(auth()->user());

        session()->flash('status', __('biodata.submitted'));
        $this->redirectRoute('my-biodata');
    }

    public function back(): void
    {
        $this->notice = '';
        $this->resetErrorBag();
        $this->step = max(1, $this->step - 1);
    }

    /** Jump to an earlier step (or the furthest finished one). Never beyond it. */
    public function goTo(int $target, BiodataProgress $progress): void
    {
        $this->notice = '';
        $this->resetErrorBag();

        $limit = $progress->reachable($this->currentBiodata(), self::STEPS);
        $this->step = max(1, min($target, $limit));
    }

    public function addEducation(): void
    {
        if (count($this->educations) < self::MAX_EDUCATIONS) {
            $this->educations[] = $this->blankEducation();
        }
    }

    public function removeEducation(int $index): void
    {
        if (count($this->educations) <= 1) {
            return;
        }

        unset($this->educations[$index]);
        $this->educations = array_values($this->educations);
        $this->resetErrorBag();
    }

    public function render()
    {
        $key = self::STEPS[max(1, min($this->step, count(self::STEPS))) - 1];

        return view('livewire.biodata.biodata-wizard', [
            'stepKey' => $key,
            'stepLabels' => array_map(fn (string $step): string => __('biodata.steps.'.$step), self::STEPS),
            'status' => BiodataStatus::tryFrom($this->statusValue),
            'options' => $this->options(),
            'districts' => in_array($key, ['address', 'preferences'], true) ? District::query()->orderBy('name_en')->get() : collect(),
            'canSubmit' => $this->readyToSubmit(),
            'countries' => Countries::options(),
            'maxEducations' => self::MAX_EDUCATIONS,
        ])->title(__('biodata.title'));
    }

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------

    /** @return array<string, array<int, mixed>> */
    private function rulesFor(string $step): array
    {
        return match ($step) {
            'general' => $this->generalRules(),
            'address' => $this->addressRules(),
            'education' => $this->educationRules(),
            'marriage' => $this->marriageRules(),
            'preferences' => $this->preferenceRules(),
            'guardian' => $this->guardianRules(),
            'work' => $this->workRules(),
            'religion' => $this->religionRules(),
            'family' => $this->familyRules(),
            'personal' => $this->personalRules(),
        };
    }

    /** @return array<string, array<int, mixed>> */
    private function generalRules(): array
    {
        $rules = [
            'marital_status' => ['required', Rule::enum(MaritalStatus::class)],
            'birth_date' => [
                'required', 'date_format:Y-m-d',
                'before_or_equal:'.now()->subYears(18)->toDateString(),
                'after_or_equal:'.now()->subYears(80)->toDateString(),
            ],
            'height_cm' => ['required', 'integer', 'between:100,230'],
            'weight_kg' => ['nullable', 'integer', 'between:30,250'],
            'complexion' => ['nullable', Rule::enum(Complexion::class)],
            'blood_group' => ['nullable', Rule::enum(BloodGroup::class)],
        ];

        if (! $this->typeLocked) {
            $rules['type'] = ['required', Rule::enum(BiodataType::class)];
        }

        return $rules;
    }

    /** @return array<string, array<int, mixed>> */
    private function addressRules(): array
    {
        $abroad = Countries::isAbroad($this->present_country);

        return [
            'present_country' => ['required', Rule::in(Countries::codes())],
            'present_district_id' => [
                Rule::requiredIf(! $abroad), 'nullable', 'integer', Rule::exists('districts', 'id'),
            ],
            'present_city' => [
                Rule::requiredIf($abroad), 'nullable', 'string', 'max:100', 'regex:'.self::TEXT_PATTERN,
            ],
            'permanent_district_id' => ['required', 'integer', Rule::exists('districts', 'id')],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function educationRules(): array
    {
        return [
            'educations' => ['required', 'array', 'min:1', 'max:'.self::MAX_EDUCATIONS],
            'educations.*' => ['array:degree,level,institution,passing_year'],
            'educations.*.degree' => ['required', 'string', 'max:120', 'regex:'.self::TEXT_PATTERN],
            'educations.*.level' => ['required', Rule::enum(EducationLevel::class)],
            'educations.*.institution' => ['required', 'string', 'max:150', 'regex:'.self::TEXT_PATTERN],
            'educations.*.passing_year' => ['required', 'integer', 'between:1970,'.((int) now()->year + 6)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function workRules(): array
    {
        return [
            'profession' => ['required', 'string', 'max:120', 'regex:'.self::TEXT_PATTERN, new NoContactDetails],
            'employer' => ['nullable', 'string', 'max:150', 'regex:'.self::TEXT_PATTERN, new NoContactDetails],
            'income_range' => ['required', Rule::enum(IncomeRange::class)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function religionRules(): array
    {
        return [
            'prayer_habit' => ['required', Rule::enum(PrayerHabit::class)],
            'quran_reading' => ['required', Rule::enum(QuranReading::class)],
            'madhhab' => ['required', Rule::enum(Madhhab::class)],
            'practicing_level' => ['required', Rule::enum(PracticingLevel::class)],
            'dress_practice' => ['required', Rule::enum(DressPractice::class)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function familyRules(): array
    {
        return [
            'father_status' => ['required', Rule::enum(ParentStatus::class)],
            'mother_status' => ['required', Rule::enum(ParentStatus::class)],
            'brothers_count' => ['required', 'integer', 'between:0,20'],
            'sisters_count' => ['required', 'integer', 'between:0,20'],
            'family_financial_status' => ['required', Rule::enum(FamilyFinancialStatus::class)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function personalRules(): array
    {
        return [
            'hobbies' => ['nullable', 'string', 'max:255', 'regex:'.self::TEXT_PATTERN, new NoContactDetails],
            'about_me' => ['required', 'string', 'min:20', 'max:1500', new NoContactDetails],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function marriageRules(): array
    {
        return [
            'marriage_timeline' => ['required', Rule::enum(MarriageTimeline::class)],
            'spouse_work_after_marriage' => ['required', Rule::enum(SpouseWorkAfterMarriage::class)],
            'living_arrangement' => ['required', Rule::enum(LivingArrangement::class)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function preferenceRules(): array
    {
        return [
            'age_min' => ['required', 'integer', 'between:18,80'],
            'age_max' => ['required', 'integer', 'between:18,80', 'gte:age_min'],
            'height_min_cm' => ['nullable', 'integer', 'between:100,230'],
            'min_education_level' => ['nullable', Rule::enum(EducationLevel::class)],
            'preferred_country' => ['nullable', Rule::in(Countries::codes())],
            'preferred_district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')],
            'marital_statuses' => ['nullable', 'array', 'max:'.count(MaritalStatus::cases())],
            'marital_statuses.*' => ['distinct', Rule::enum(MaritalStatus::class)],
            'min_practicing_level' => ['nullable', Rule::enum(PracticingLevel::class)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function guardianRules(): array
    {
        return [
            'guardian_name' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\p{L}\p{M}\s.'\-]+$/u"],
            'guardian_relation' => ['required', Rule::enum(GuardianRelation::class)],
            'guardian_phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'guardian_can_approve' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    private function attributeNames(): array
    {
        return [
            'type' => __('biodata.fields.type'),
            'marital_status' => __('biodata.fields.marital_status'),
            'birth_date' => __('biodata.fields.birth_date'),
            'height_cm' => __('biodata.fields.height_cm'),
            'weight_kg' => __('biodata.fields.weight_kg'),
            'complexion' => __('biodata.fields.complexion'),
            'blood_group' => __('biodata.fields.blood_group'),
            'present_country' => __('biodata.fields.present_country'),
            'present_district_id' => __('biodata.fields.present_district'),
            'present_city' => __('biodata.fields.present_city'),
            'permanent_district_id' => __('biodata.fields.permanent_district'),
            'educations' => __('biodata.steps.education'),
            'educations.*.degree' => __('biodata.fields.degree'),
            'educations.*.level' => __('biodata.fields.education_level'),
            'educations.*.institution' => __('biodata.fields.institution'),
            'educations.*.passing_year' => __('biodata.fields.passing_year'),
            'profession' => __('biodata.fields.profession'),
            'employer' => __('biodata.fields.employer'),
            'income_range' => __('biodata.fields.income_range'),
            'prayer_habit' => __('biodata.fields.prayer_habit'),
            'quran_reading' => __('biodata.fields.quran_reading'),
            'madhhab' => __('biodata.fields.madhhab'),
            'practicing_level' => __('biodata.fields.practicing_level'),
            'dress_practice' => __('biodata.fields.dress_practice'),
            'father_status' => __('biodata.fields.father_status'),
            'mother_status' => __('biodata.fields.mother_status'),
            'brothers_count' => __('biodata.fields.brothers_count'),
            'sisters_count' => __('biodata.fields.sisters_count'),
            'family_financial_status' => __('biodata.fields.family_financial_status'),
            'hobbies' => __('biodata.fields.hobbies'),
            'about_me' => __('biodata.fields.about_me'),
            'marriage_timeline' => __('biodata.fields.marriage_timeline'),
            'spouse_work_after_marriage' => __('biodata.fields.spouse_work_after_marriage'),
            'living_arrangement' => __('biodata.fields.living_arrangement'),
            'age_min' => __('biodata.fields.age_min'),
            'age_max' => __('biodata.fields.age_max'),
            'height_min_cm' => __('biodata.fields.height_min_cm'),
            'min_education_level' => __('biodata.fields.min_education_level'),
            'preferred_country' => __('biodata.fields.preferred_country'),
            'preferred_district_id' => __('biodata.fields.preferred_district_id'),
            'marital_statuses' => __('biodata.fields.marital_statuses'),
            'marital_statuses.*' => __('biodata.fields.marital_statuses'),
            'min_practicing_level' => __('biodata.fields.min_practicing_level'),
            'guardian_name' => __('biodata.fields.guardian_name'),
            'guardian_relation' => __('biodata.fields.guardian_relation'),
            'guardian_phone' => __('biodata.fields.guardian_phone'),
            'guardian_can_approve' => __('biodata.fields.guardian_can_approve'),
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function currentBiodata(): ?Biodata
    {
        return Biodata::query()
            ->where('user_id', auth()->id())
            ->with(['educations', 'preference', 'guardian'])
            ->first();
    }

    /** Stops a member from opening a step they have not reached (public $step is client-writable). */
    private function clampStep(BiodataProgress $progress): int
    {
        $limit = $progress->reachable($this->currentBiodata(), self::STEPS);

        return max(1, min($this->step, $limit));
    }

    private function syncState(Biodata $biodata): void
    {
        $this->statusValue = $biodata->status->value;
        $this->rejectionReason = (string) $biodata->rejection_reason;
        $this->typeLocked = $biodata->submitted_at !== null;
        $this->readOnly = Gate::denies('update', $biodata);
    }

    private function fillFrom(Biodata $biodata): void
    {
        $this->type = $biodata->type?->value ?? '';
        $this->marital_status = $biodata->marital_status?->value ?? '';
        $this->birth_date = $biodata->birth_date?->format('Y-m-d') ?? '';
        $this->height_cm = (string) ($biodata->height_cm ?? '');
        $this->weight_kg = (string) ($biodata->weight_kg ?? '');
        $this->complexion = $biodata->complexion?->value ?? '';
        $this->blood_group = $biodata->blood_group?->value ?? '';

        $this->present_country = $biodata->present_country ?: Countries::HOME;
        $this->present_district_id = (string) ($biodata->present_district_id ?? '');
        $this->present_city = (string) ($biodata->present_city ?? '');
        $this->permanent_district_id = (string) ($biodata->permanent_district_id ?? '');

        $this->profession = (string) ($biodata->profession ?? '');
        $this->employer = (string) ($biodata->employer ?? '');
        $this->income_range = $biodata->income_range?->value ?? '';

        $this->prayer_habit = $biodata->prayer_habit?->value ?? '';
        $this->quran_reading = $biodata->quran_reading?->value ?? '';
        $this->madhhab = $biodata->madhhab?->value ?? '';
        $this->practicing_level = $biodata->practicing_level?->value ?? '';
        $this->dress_practice = $biodata->dress_practice?->value ?? '';

        $this->father_status = $biodata->father_status?->value ?? '';
        $this->mother_status = $biodata->mother_status?->value ?? '';
        $this->brothers_count = (string) ($biodata->brothers_count ?? '');
        $this->sisters_count = (string) ($biodata->sisters_count ?? '');
        $this->family_financial_status = $biodata->family_financial_status?->value ?? '';

        $this->hobbies = (string) ($biodata->hobbies ?? '');
        $this->about_me = (string) ($biodata->about_me ?? '');

        $this->marriage_timeline = $biodata->marriage_timeline?->value ?? '';
        $this->spouse_work_after_marriage = $biodata->spouse_work_after_marriage?->value ?? '';
        $this->living_arrangement = $biodata->living_arrangement?->value ?? '';

        $preference = $biodata->preference;
        $this->age_min = (string) ($preference?->age_min ?? '');
        $this->age_max = (string) ($preference?->age_max ?? '');
        $this->height_min_cm = (string) ($preference?->height_min_cm ?? '');
        $this->min_education_level = $preference?->min_education_level?->value ?? '';
        $this->preferred_country = (string) ($preference?->preferred_country ?? '');
        $this->preferred_district_id = (string) ($preference?->preferred_district_id ?? '');
        $this->marital_statuses = $preference?->marital_statuses?->map(fn ($status) => $status->value)->values()->all() ?? [];
        $this->min_practicing_level = $preference?->min_practicing_level?->value ?? '';

        $guardian = $biodata->guardian;
        $this->guardian_name = (string) ($guardian?->name ?? '');
        $this->guardian_relation = $guardian?->relation?->value ?? '';
        $this->guardian_phone = (string) ($guardian?->phone ?? '');
        $this->guardian_can_approve = $guardian?->can_approve ?? true;

        $this->educations = $biodata->educations
            ->map(fn ($row): array => [
                'degree' => $row->degree,
                'level' => $row->level?->value ?? '',
                'institution' => $row->institution,
                'passing_year' => (string) $row->passing_year,
            ])
            ->values()
            ->all() ?: [$this->blankEducation()];
    }

    /** @return array{degree: string, level: string, institution: string, passing_year: string} */
    private function blankEducation(): array
    {
        return ['degree' => '', 'level' => '', 'institution' => '', 'passing_year' => ''];
    }

    /** Trim text and turn Bangla digits into ASCII digits before validating. */
    private function normalize(): void
    {
        foreach (['height_cm', 'weight_kg', 'brothers_count', 'sisters_count', 'age_min', 'age_max', 'height_min_cm'] as $field) {
            $this->{$field} = trim((string) Digits::normalize($this->{$field}));
        }

        $this->present_city = $this->squish($this->present_city);
        $this->guardian_name = $this->squish($this->guardian_name);
        $this->guardian_phone = (string) preg_replace('/[\s\-().]/u', '', (string) Digits::normalize($this->guardian_phone));
        $this->marital_statuses = array_values(array_filter($this->marital_statuses, 'is_string'));
        $this->profession = $this->squish($this->profession);
        $this->employer = $this->squish($this->employer);
        $this->hobbies = $this->squish($this->hobbies);
        $this->about_me = trim((string) preg_replace('/[^\P{C}\n]/u', '', $this->about_me));

        $rows = [];

        foreach ($this->educations as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = [
                'degree' => $this->squish($row['degree'] ?? ''),
                'level' => $this->squish($row['level'] ?? ''),
                'institution' => $this->squish($row['institution'] ?? ''),
                'passing_year' => trim((string) Digits::normalize((string) ($row['passing_year'] ?? ''))),
            ];
        }

        $this->educations = $rows;
    }

    private function squish(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', is_scalar($value) ? (string) $value : ''));
    }

    private function canSubmit(Biodata $biodata, BiodataProgress $progress): bool
    {
        return $progress->firstIncomplete($biodata, BiodataProgress::ALL_STEPS) === null
            && Gate::allows('submit', $biodata);
    }

    private function readyToSubmit(): bool
    {
        $biodata = $this->currentBiodata();

        return $biodata !== null && $this->canSubmit($biodata, app(BiodataProgress::class));
    }

    /** At most 120 saves an hour per member. */
    private function throttle(): void
    {
        $key = 'biodata-save:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 120)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    /** @return array<string, array<string, string>> */
    private function options(): array
    {
        $labels = fn (array $cases): array => collect($cases)
            ->mapWithKeys(fn ($case): array => [$case->value => $case->label()])
            ->all();

        return [
            'type' => $labels(BiodataType::cases()),
            'marital_status' => $labels(MaritalStatus::cases()),
            'complexion' => $labels(Complexion::cases()),
            'blood_group' => $labels(BloodGroup::cases()),
            'income_range' => $labels(IncomeRange::cases()),
            'prayer_habit' => $labels(PrayerHabit::cases()),
            'quran_reading' => $labels(QuranReading::cases()),
            'madhhab' => $labels(Madhhab::cases()),
            'practicing_level' => $labels(PracticingLevel::cases()),
            'dress_practice' => $labels(DressPractice::cases()),
            'parent_status' => $labels(ParentStatus::cases()),
            'family_financial_status' => $labels(FamilyFinancialStatus::cases()),
            'marriage_timeline' => $labels(MarriageTimeline::cases()),
            'spouse_work_after_marriage' => $labels(SpouseWorkAfterMarriage::cases()),
            'living_arrangement' => $labels(LivingArrangement::cases()),
            'education_level' => $labels(EducationLevel::cases()),
            'guardian_relation' => $labels(GuardianRelation::cases()),
        ];
    }
}
