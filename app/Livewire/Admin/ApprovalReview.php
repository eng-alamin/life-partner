<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Biodata\ReviewBiodata;
use App\Enums\BiodataStatus;
use App\Models\Biodata;
use App\Models\District;
use App\Support\Countries;
use BackedEnum;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One biodata under review. The biodata id and the revision the reviewer saw
 * are locked (the browser cannot change them). Approve and reject re-check
 * everything on the server inside ReviewBiodata.
 */
#[Layout('layouts::admin')]
class ApprovalReview extends Component
{
    #[Locked]
    public int $biodataId = 0;

    #[Locked]
    public int $seenRevision = 0;

    public string $rejectReason = '';

    public function mount(Biodata $biodata): void
    {
        Gate::authorize('review', $biodata);

        $this->biodataId = (int) $biodata->getKey();
        $this->seenRevision = (int) $biodata->revision;
    }

    public function approve(ReviewBiodata $review): void
    {
        $this->throttle();

        $review->approve(auth()->user(), $this->biodataId, $this->seenRevision);

        session()->flash('status', __('admin.flash.approved'));
        $this->redirectRoute('admin.approvals');
    }

    public function reject(ReviewBiodata $review): void
    {
        $this->throttle();

        $this->rejectReason = trim((string) preg_replace('/[^\P{C}\n]/u', '', $this->rejectReason));

        $this->validate(
            ['rejectReason' => ['required', 'string', 'min:10', 'max:500']],
            [],
            ['rejectReason' => __('admin.review.reason_label')],
        );

        $review->reject(auth()->user(), $this->biodataId, $this->seenRevision, $this->rejectReason);

        session()->flash('status', __('admin.flash.rejected'));
        $this->redirectRoute('admin.approvals');
    }

    /** Loads the newest version of the page (used after the member changed it). */
    public function reload(): void
    {
        $this->redirectRoute('admin.approvals.show', ['biodata' => $this->current()->public_code]);
    }

    public function render()
    {
        $biodata = $this->current();

        Gate::authorize('review', $biodata);

        return view('livewire.admin.approval-review', [
            'biodata' => $biodata,
            'canDecide' => $biodata->status === BiodataStatus::Pending,
            'isStale' => $biodata->revision !== $this->seenRevision,
            'sections' => $this->sections($biodata),
        ])->title(__('admin.review.title'));
    }

    private function current(): Biodata
    {
        return Biodata::query()
            ->with(['educations', 'preference.preferredDistrict', 'guardian', 'presentDistrict', 'permanentDistrict'])
            ->findOrFail($this->biodataId);
    }

    /**
     * Label/value rows for the review page. Staff see everything needed to
     * judge the profile, but not the guardian's name or phone and not the
     * member's account email: they are not needed to approve a biodata.
     *
     * @return list<array{title: string, rows: list<array{0: string, 1: string}>, long?: bool}>
     */
    private function sections(Biodata $b): array
    {
        $none = __('admin.review.none');
        $f = fn (string $key): string => __('biodata.fields.'.$key);
        $label = fn (mixed $value): string => $value instanceof BackedEnum ? (string) $value->label() : $none;
        $text = fn (mixed $value): string => filled($value) ? (string) $value : $none;
        $place = fn (?District $district): string => $district?->name() ?? $none;

        $p = $b->preference;

        $education = $b->educations->map(fn ($row): array => [
            $row->degree,
            $row->institution.' ('.$row->passing_year.')',
        ])->all();

        $sections = [
            ['title' => __('biodata.steps.general'), 'rows' => [
                [$f('type'), $label($b->type)],
                [$f('marital_status'), $label($b->marital_status)],
                [$f('birth_date'), $b->birth_date ? $b->birth_date->format('Y-m-d').' ('.$b->age.')' : $none],
                [$f('height_cm'), $text($b->height_cm)],
                [$f('weight_kg'), $text($b->weight_kg)],
                [$f('complexion'), $label($b->complexion)],
                [$f('blood_group'), $label($b->blood_group)],
            ]],
            ['title' => __('biodata.steps.address'), 'rows' => [
                [$f('present_country'), $text(Countries::name($b->present_country))],
                [$f('present_district'), $place($b->presentDistrict)],
                [$f('present_city'), $text($b->present_city)],
                [$f('permanent_district'), $place($b->permanentDistrict)],
            ]],
            ['title' => __('biodata.steps.education'), 'rows' => $education !== [] ? $education : [[$none, $none]]],
            ['title' => __('biodata.steps.work'), 'rows' => [
                [$f('profession'), $text($b->profession)],
                [$f('employer'), $text($b->employer)],
                [$f('income_range'), $label($b->income_range)],
            ]],
            ['title' => __('biodata.steps.religion'), 'rows' => [
                [$f('prayer_habit'), $label($b->prayer_habit)],
                [$f('quran_reading'), $label($b->quran_reading)],
                [$f('madhhab'), $label($b->madhhab)],
                [$f('practicing_level'), $label($b->practicing_level)],
                [$f('dress_practice'), $label($b->dress_practice)],
            ]],
            ['title' => __('biodata.steps.family'), 'rows' => [
                [$f('father_status'), $label($b->father_status)],
                [$f('mother_status'), $label($b->mother_status)],
                [$f('brothers_count'), $text($b->brothers_count)],
                [$f('sisters_count'), $text($b->sisters_count)],
                [$f('family_financial_status'), $label($b->family_financial_status)],
            ]],
            ['title' => __('biodata.steps.personal'), 'long' => true, 'rows' => [
                [$f('hobbies'), $text($b->hobbies)],
                [$f('about_me'), $text($b->about_me)],
            ]],
            ['title' => __('biodata.steps.marriage'), 'rows' => [
                [$f('marriage_timeline'), $label($b->marriage_timeline)],
                [$f('spouse_work_after_marriage'), $label($b->spouse_work_after_marriage)],
                [$f('living_arrangement'), $label($b->living_arrangement)],
            ]],
            ['title' => __('biodata.steps.preferences'), 'rows' => [
                [__('admin.review.age_range'), $p ? $p->age_min.' – '.$p->age_max : $none],
                [$f('height_min_cm'), $text($p?->height_min_cm)],
                [$f('min_education_level'), $label($p?->min_education_level)],
                [$f('preferred_country'), $text(Countries::name($p?->preferred_country))],
                [$f('preferred_district_id'), $place($p?->preferredDistrict)],
                [$f('marital_statuses'), $p?->marital_statuses?->isNotEmpty() ? $p->marital_statuses->map(fn ($s) => $s->label())->implode(', ') : $none],
                [$f('min_practicing_level'), $label($p?->min_practicing_level)],
            ]],
            ['title' => __('biodata.steps.guardian'), 'rows' => [
                [$f('guardian_relation'), $label($b->guardian?->relation)],
                [$f('guardian_can_approve'), $b->guardian ? ($b->guardian->can_approve ? __('admin.review.yes') : __('admin.review.no')) : $none],
            ]],
        ];

        return $sections;
    }

    /** At most 60 decisions an hour per staff member. */
    private function throttle(): void
    {
        $key = 'biodata-review:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
