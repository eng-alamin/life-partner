<?php

declare(strict_types=1);

namespace App\Actions\Plan;

use App\Enums\PlanKind;
use App\Models\Plan;
use App\Models\User;
use App\Support\Digits;
use App\Support\TakaInput;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Creates a plan, or updates one found by its code. Everything is checked here on
 * the server; the form only collects text.
 *
 * - The code and the kind are chosen once, when the plan is created, and never change
 *   (old payments keep their own saved copy of the plan, see StartCheckout).
 * - The price is typed in taka and stored as whole poisha.
 * - A plan can only be active with a price of at least 1 poisha.
 * - A credit pack gives credits only. A membership needs a length in days and must
 *   give something (credits, a higher daily interest limit, or both).
 * - The activity log records the plan code and the changed numbers, nothing personal.
 */
final class SavePlan
{
    private const TRACKED = [
        'name_bn', 'name_en', 'description_bn', 'description_en', 'price_minor',
        'credits', 'duration_days', 'daily_interest_limit', 'sort_order', 'is_active',
    ];

    private const NUMERIC_TRACKED = ['price_minor', 'credits', 'duration_days', 'daily_interest_limit', 'sort_order', 'is_active'];

    /**
     * @param  array<string, mixed>  $input  code, kind, name_bn, name_en, description_bn, description_en,
     *                                       price, credits, duration_days, daily_interest_limit, sort_order, is_active
     */
    public function __invoke(User $staff, ?string $planCode, array $input): Plan
    {
        $gate = Gate::forUser($staff);

        if ($planCode === null) {
            $gate->authorize('create', Plan::class);
        }

        try {
            return DB::transaction(fn (): Plan => $this->save($staff, $planCode, $input));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => __('admin_plans.errors.code_taken')]);
        }
    }

    /** @param  array<string, mixed>  $input */
    private function save(User $staff, ?string $planCode, array $input): Plan
    {
        $gate = Gate::forUser($staff);

        if ($planCode === null) {
            $plan = new Plan;
            $kind = PlanKind::tryFrom($this->text($input['kind'] ?? ''));

            if ($kind === null) {
                throw ValidationException::withMessages(['kind' => __('admin_plans.errors.kind_invalid')]);
            }

            $code = $this->code($input['code'] ?? '');
        } else {
            $plan = Plan::query()->where('code', $planCode)->lockForUpdate()->firstOrFail();
            $gate->authorize('update', $plan);

            $kind = $plan->kind;
            $code = $plan->code;
        }

        $data = $this->validated($kind, $input);

        if ($plan->exists) {
            return $this->update($staff, $plan, $data);
        }

        $plan->forceFill([
            'code' => $code,
            'kind' => $kind->value,
            'currency' => 'BDT',
            ...$data,
        ])->save();

        activity('plan')
            ->causedBy($staff)
            ->performedOn($plan)
            ->withProperties([
                'plan_code' => $plan->code,
                'kind' => $kind->value,
                'price_minor' => $plan->price_minor,
                'credits' => $plan->credits,
                'duration_days' => $plan->duration_days,
                'daily_interest_limit' => $plan->daily_interest_limit,
                'is_active' => $plan->is_active,
            ])
            ->log('plan.created');

        return $plan;
    }

    /** @param  array<string, mixed>  $data */
    private function update(User $staff, Plan $plan, array $data): Plan
    {
        $changed = [];
        $numbers = [];

        foreach (self::TRACKED as $field) {
            $old = $plan->getAttribute($field);

            if ($this->same($old, $data[$field])) {
                continue;
            }

            $changed[] = $field;

            if (in_array($field, self::NUMERIC_TRACKED, true)) {
                $numbers[$field] = ['old' => $old, 'new' => $data[$field]];
            }
        }

        if ($changed === []) {
            return $plan;
        }

        $plan->forceFill($data)->save();

        activity('plan')
            ->causedBy($staff)
            ->performedOn($plan)
            ->withProperties(['plan_code' => $plan->code, 'fields' => $changed, 'changes' => $numbers])
            ->log('plan.updated');

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validated(PlanKind $kind, array $input): array
    {
        $nameBn = $this->text($input['name_bn'] ?? '');
        $nameEn = $this->text($input['name_en'] ?? '');

        foreach (['name_bn' => $nameBn, 'name_en' => $nameEn] as $key => $value) {
            if ($value === '' || mb_strlen($value) > 120) {
                throw ValidationException::withMessages([$key => __('admin_plans.errors.name_invalid', ['max' => Digits::localize(120)])]);
            }
        }

        $descriptions = [];

        foreach (['description_bn', 'description_en'] as $key) {
            $value = $this->text($input[$key] ?? '');

            if (mb_strlen($value) > 500) {
                throw ValidationException::withMessages([$key => __('admin_plans.errors.description_invalid', ['max' => Digits::localize(500)])]);
            }

            $descriptions[$key] = $value === '' ? null : $value;
        }

        $price = TakaInput::toMinor($this->text($input['price'] ?? ''));

        if ($price === null) {
            throw ValidationException::withMessages(['price' => __('admin_plans.errors.price_invalid')]);
        }

        $active = filter_var($input['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($active && $price < 1) {
            throw ValidationException::withMessages(['price' => __('admin_plans.errors.price_required_active')]);
        }

        $sort = $this->whole($input['sort_order'] ?? '', 'sort_order', 0, 9999) ?? 0;

        if ($kind === PlanKind::CreditPack) {
            $credits = $this->whole($input['credits'] ?? '', 'credits', 1, 10000);

            if ($credits === null) {
                throw ValidationException::withMessages(['credits' => __('admin_plans.errors.credits_required')]);
            }

            $days = null;
            $limit = null;
        } else {
            $credits = $this->whole($input['credits'] ?? '', 'credits', 0, 10000) ?? 0;
            $days = $this->whole($input['duration_days'] ?? '', 'duration_days', 1, 3650);

            if ($days === null) {
                throw ValidationException::withMessages(['duration_days' => __('admin_plans.errors.days_required')]);
            }

            $limit = $this->whole($input['daily_interest_limit'] ?? '', 'daily_interest_limit', 1, 1000);

            if ($credits === 0 && $limit === null) {
                throw ValidationException::withMessages(['credits' => __('admin_plans.errors.gives_nothing')]);
            }
        }

        return [
            'name_bn' => $nameBn,
            'name_en' => $nameEn,
            'description_bn' => $descriptions['description_bn'],
            'description_en' => $descriptions['description_en'],
            'price_minor' => $price,
            'credits' => $credits,
            'duration_days' => $days,
            'daily_interest_limit' => $limit,
            'sort_order' => $sort,
            'is_active' => $active,
        ];
    }

    /** Equal even when the database gave a number as text (sort_order has no cast). */
    private function same(mixed $old, mixed $new): bool
    {
        if ($old === null || $new === null) {
            return $old === $new;
        }

        return (string) (is_bool($old) ? (int) $old : $old) === (string) (is_bool($new) ? (int) $new : $new);
    }

    private function code(mixed $value): string
    {
        $code = mb_strtolower($this->text($value));

        if (mb_strlen($code) < 3 || mb_strlen($code) > 40 || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $code) !== 1) {
            throw ValidationException::withMessages(['code' => __('admin_plans.errors.code_invalid')]);
        }

        if (Plan::query()->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => __('admin_plans.errors.code_taken')]);
        }

        return $code;
    }

    /** A whole number in a range, or null when the box was left empty. */
    private function whole(mixed $value, string $field, int $min, int $max): ?int
    {
        $text = preg_replace('/\s+/u', '', (string) Digits::normalize(is_scalar($value) ? (string) $value : '')) ?? '';

        if ($text === '') {
            return null;
        }

        if (preg_match('/^\d{1,6}$/', $text) !== 1 || (int) $text < $min || (int) $text > $max) {
            throw ValidationException::withMessages([$field => __('admin_plans.errors.number_invalid', ['min' => Digits::localize($min), 'max' => Digits::localize($max)])]);
        }

        return (int) $text;
    }

    /** Trimmed text with repeated spaces collapsed. Anything that is not text becomes empty. */
    private function text(mixed $value): string
    {
        if (! is_string($value) && ! is_int($value)) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }
}
