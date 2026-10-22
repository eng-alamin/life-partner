<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlanKind;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'code' => 'plan-'.fake()->unique()->bothify('????##'),
            'kind' => PlanKind::CreditPack->value,
            'name_bn' => 'ক্রেডিট প্যাক',
            'name_en' => 'Credit pack',
            'price_minor' => 50000,
            'currency' => 'BDT',
            'credits' => 10,
            'duration_days' => null,
            'daily_interest_limit' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function membership(int $days = 30, int $dailyLimit = 15, int $credits = 0): static
    {
        return $this->state(fn (): array => [
            'kind' => PlanKind::Membership->value,
            'name_bn' => 'মাসিক সদস্যপদ',
            'name_en' => 'Monthly membership',
            'credits' => $credits,
            'duration_days' => $days,
            'daily_interest_limit' => $dailyLimit,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
