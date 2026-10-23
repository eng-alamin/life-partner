<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\Biodata;
use App\Models\BiodataPhoto;
use App\Models\ContactMessage;
use App\Models\CreditLedgerEntry;
use App\Models\District;
use App\Models\IgnoredBiodata;
use App\Models\Interest;
use App\Models\Payment;
use App\Models\Shortlist;
use App\Models\Subscription;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Collects the data one member gave us or created, as a plain array ready for JSON.
 *
 * Only the member's own data goes in. It never contains:
 * - another member's name, contact details, photos or text (other members appear only by public code),
 * - what another member wrote to this member in an interest request,
 * - staff data (rejection reasons, review notes, who reviewed what, reports, flags),
 * - the password hash, remember token, one-time codes, guardian link tokens or gateway references,
 * - the picture files themselves or the identity document files.
 */
final class BuildMemberExport
{
    public const FORMAT_VERSION = 1;

    /** @return array<string, mixed> */
    public function __invoke(User $user): array
    {
        $biodata = Biodata::query()
            ->where('user_id', $user->getKey())
            ->with(['educations', 'preference', 'guardian', 'photos'])
            ->first();

        $sent = $biodata === null
            ? collect()
            : Interest::query()->where('sender_biodata_id', $biodata->getKey())->orderBy('id')->get();
        $received = $biodata === null
            ? collect()
            : Interest::query()->where('receiver_biodata_id', $biodata->getKey())->orderBy('id')->get();
        $shortlist = Shortlist::query()->where('user_id', $user->getKey())->orderBy('id')->get();
        $ignored = IgnoredBiodata::query()->where('user_id', $user->getKey())->orderBy('id')->get();

        $codes = $this->publicCodes(
            $sent->pluck('receiver_biodata_id')
                ->merge($received->pluck('sender_biodata_id'))
                ->merge($shortlist->pluck('biodata_id'))
                ->merge($ignored->pluck('biodata_id'))
        );

        return [
            'service' => 'Life Partner',
            'format_version' => self::FORMAT_VERSION,
            'generated_at' => now()->toIso8601String(),
            'account' => $this->account($user),
            'biodata' => $biodata === null ? null : $this->biodata($biodata),
            'interests_sent' => $sent->map(fn (Interest $i): array => [
                'to_biodata' => $codes->get($i->receiver_biodata_id),
                'status' => $this->value($i->status),
                'your_message' => $i->message,
                'sent_at' => $this->value($i->created_at),
                'responded_at' => $this->value($i->responded_at),
            ])->values()->all(),
            'interests_received' => $received->map(fn (Interest $i): array => [
                'from_biodata' => $codes->get($i->sender_biodata_id),
                'status' => $this->value($i->status),
                'received_at' => $this->value($i->created_at),
                'responded_at' => $this->value($i->responded_at),
            ])->values()->all(),
            'shortlist' => $shortlist->map(fn (Shortlist $s): array => [
                'biodata' => $codes->get($s->biodata_id),
                'added_at' => $this->value($s->created_at),
            ])->values()->all(),
            'ignored' => $ignored->map(fn (IgnoredBiodata $s): array => [
                'biodata' => $codes->get($s->biodata_id),
                'added_at' => $this->value($s->created_at),
            ])->values()->all(),
            'saved_searches' => $user->savedSearches()->orderBy('id')->get()->map(fn ($s): array => [
                'name' => $s->name,
                'filters' => $s->filters,
                'created_at' => $this->value($s->created_at),
            ])->values()->all(),
            'subscriptions' => Subscription::query()->where('user_id', $user->getKey())->orderBy('id')->get()
                ->map(fn (Subscription $s): array => [
                    'plan' => $s->plan_name,
                    'status' => $this->value($s->status),
                    'starts_at' => $this->value($s->starts_at),
                    'ends_at' => $this->value($s->ends_at),
                    'cancelled_at' => $this->value($s->cancelled_at),
                ])->values()->all(),
            'payments' => Payment::query()->where('user_id', $user->getKey())->orderBy('id')->get()
                ->map(fn (Payment $p): array => [
                    'reference' => $p->tran_id,
                    'plan' => $p->plan_name,
                    'credits' => $p->credits,
                    'duration_days' => $p->duration_days,
                    'amount_minor' => (int) $p->amount_minor,
                    'amount' => sprintf('%d.%02d', intdiv((int) $p->amount_minor, 100), (int) $p->amount_minor % 100),
                    'currency' => $p->currency,
                    'gateway' => $this->value($p->gateway),
                    'status' => $this->value($p->status),
                    'created_at' => $this->value($p->created_at),
                    'paid_at' => $this->value($p->paid_at),
                ])->values()->all(),
            'credit_ledger' => CreditLedgerEntry::query()->where('user_id', $user->getKey())->orderBy('id')->get()
                ->map(fn (CreditLedgerEntry $e): array => [
                    'change' => (int) $e->delta,
                    'reason' => $this->value($e->reason),
                    'expires_at' => $this->value($e->expires_at),
                    'created_at' => $this->value($e->created_at),
                ])->values()->all(),
            'notification_settings' => DB::table('notification_preferences')
                ->where('user_id', $user->getKey())
                ->orderBy('category')
                ->get(['category', 'email', 'sms'])
                ->map(fn (object $r): array => [
                    'category' => $r->category,
                    'email' => (bool) $r->email,
                    'sms' => (bool) $r->sms,
                ])->values()->all(),
            'contact_messages' => ContactMessage::query()->where('user_id', $user->getKey())->orderBy('id')->get()
                ->map(fn (ContactMessage $m): array => [
                    'subject' => $m->subject,
                    'message' => $m->message,
                    'status' => $this->value($m->status),
                    'sent_at' => $this->value($m->created_at),
                ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function account(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'language' => $user->locale,
            'joined_at' => $this->value($user->created_at),
            'email_verified_at' => $this->value($user->email_verified_at),
            'phone_verified_at' => $this->value($user->phone_verified_at),
            'last_login_at' => $this->value($user->last_login_at),
            'deletion_scheduled_for' => $this->value($user->deletion_scheduled_for),
        ];
    }

    /** @return array<string, mixed> */
    private function biodata(Biodata $b): array
    {
        $districtIds = array_values(array_filter([
            $b->present_district_id,
            $b->permanent_district_id,
            $b->preference?->preferred_district_id,
        ]));
        $districts = $districtIds === []
            ? collect()
            : District::query()->whereIn('id', $districtIds)->get()->keyBy('id');

        $preference = $b->preference;
        $guardian = $b->guardian;

        return [
            'public_code' => $b->public_code,
            'status' => $this->value($b->status),
            'submitted_at' => $this->value($b->submitted_at),
            'created_at' => $this->value($b->created_at),
            'updated_at' => $this->value($b->updated_at),
            'general' => $this->pick($b, ['type', 'marital_status', 'height_cm', 'weight_kg', 'complexion', 'blood_group'])
                + ['birth_date' => $b->birth_date?->toDateString()],
            'address' => [
                'present_country' => $b->present_country,
                'present_district' => $this->district($districts, $b->present_district_id),
                'present_city' => $b->present_city,
                'permanent_district' => $this->district($districts, $b->permanent_district_id),
            ],
            'education' => $b->educations->sortBy('sort_order')->values()
                ->map(fn (Model $e): array => $this->pick($e, ['degree', 'level', 'institution', 'passing_year']))
                ->all(),
            'occupation' => $this->pick($b, ['profession', 'employer', 'income_range']),
            'religion' => $this->pick($b, ['prayer_habit', 'quran_reading', 'madhhab', 'practicing_level', 'dress_practice']),
            'family' => $this->pick($b, ['father_status', 'mother_status', 'brothers_count', 'sisters_count', 'family_financial_status']),
            'personal' => $this->pick($b, ['hobbies', 'about_me']),
            'marriage' => $this->pick($b, ['marriage_timeline', 'spouse_work_after_marriage', 'living_arrangement']),
            'partner_preferences' => $preference === null ? null : $this->pick($preference, [
                'age_min', 'age_max', 'height_min_cm', 'min_education_level',
                'preferred_country', 'marital_statuses', 'min_practicing_level',
            ]) + ['preferred_district' => $this->district($districts, $preference->preferred_district_id)],
            'guardian' => $guardian === null ? null : $this->pick($guardian, ['name', 'relation', 'phone', 'can_approve']),
            'photos' => [
                'visibility' => $this->value($b->photo_visibility),
                'items' => $b->photos->sortBy('sort_order')->values()
                    ->map(fn (BiodataPhoto $p): array => [
                        'is_main' => (bool) $p->is_main,
                        'status' => $this->value($p->status),
                        'uploaded_at' => $this->value($p->created_at),
                    ])->all(),
            ],
        ];
    }

    /**
     * Public codes of biodatas (also deleted ones) by id, so other members show up only as a code.
     *
     * @param  Collection<int, mixed>  $ids
     * @return Collection<int, string>
     */
    private function publicCodes(Collection $ids): Collection
    {
        $ids = $ids->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Biodata::withTrashed()->whereIn('id', $ids->all())->pluck('public_code', 'id');
    }

    /**
     * @param  Collection<int|string, District>  $districts
     * @return array{en: string, bn: string}|null
     */
    private function district(Collection $districts, ?int $id): ?array
    {
        $district = $id === null ? null : $districts->get($id);

        return $district === null ? null : ['en' => $district->name_en, 'bn' => $district->name_bn];
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function pick(Model $model, array $fields): array
    {
        $out = [];

        foreach ($fields as $field) {
            $out[$field] = $this->value($model->getAttribute($field));
        }

        return $out;
    }

    /** Turns enums, dates and collections into plain values that JSON can hold. */
    private function value(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof CarbonInterface => $value->toIso8601String(),
            $value instanceof Collection => $value->map(fn (mixed $v): mixed => $this->value($v))->values()->all(),
            default => $value,
        };
    }
}
