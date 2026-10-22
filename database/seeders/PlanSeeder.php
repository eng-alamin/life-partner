<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlanKind;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Adds sample plans so the admin has something to edit. They are INACTIVE and have
 * a price of 0 on purpose: the real prices are set by the owner in the admin panel.
 * A plan that already exists is never touched, so running the seeder again can not
 * overwrite prices the owner has set.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            [
                'code' => 'credits-10', 'kind' => PlanKind::CreditPack,
                'name_bn' => '১০ ক্রেডিট', 'name_en' => '10 credits',
                'credits' => 10, 'duration_days' => null, 'daily_interest_limit' => null, 'sort_order' => 10,
            ],
            [
                'code' => 'credits-30', 'kind' => PlanKind::CreditPack,
                'name_bn' => '৩০ ক্রেডিট', 'name_en' => '30 credits',
                'credits' => 30, 'duration_days' => null, 'daily_interest_limit' => null, 'sort_order' => 20,
            ],
            [
                'code' => 'monthly', 'kind' => PlanKind::Membership,
                'name_bn' => 'মাসিক সদস্যপদ', 'name_en' => 'Monthly membership',
                'credits' => 10, 'duration_days' => 30, 'daily_interest_limit' => 15, 'sort_order' => 30,
            ],
            [
                'code' => 'yearly', 'kind' => PlanKind::Membership,
                'name_bn' => 'বার্ষিক সদস্যপদ', 'name_en' => 'Yearly membership',
                'credits' => 60, 'duration_days' => 365, 'daily_interest_limit' => 25, 'sort_order' => 40,
            ],
        ];

        foreach ($samples as $sample) {
            if (Plan::query()->where('code', $sample['code'])->exists()) {
                continue;
            }

            $plan = new Plan;
            $plan->forceFill([
                ...$sample,
                'kind' => $sample['kind']->value,
                'price_minor' => 0,
                'currency' => 'BDT',
                'is_active' => false,
            ])->save();
        }
    }
}
