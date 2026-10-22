<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\PlanKind;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => null,
            'plan_code' => 'credits-10',
            'plan_name' => '10 credits',
            'plan_kind' => PlanKind::CreditPack->value,
            'credits' => 10,
            'duration_days' => null,
            'daily_interest_limit' => null,
            'amount_minor' => 50000,
            'currency' => 'BDT',
            'gateway' => PaymentGateway::ManualBkash->value,
            'status' => PaymentStatus::Pending->value,
        ];
    }

    /** Copies the plan into the payment the way checkout does. */
    public function forPlan(Plan $plan): static
    {
        return $this->state(fn (): array => [
            'plan_id' => $plan->getKey(),
            'plan_code' => $plan->code,
            'plan_name' => $plan->name_en,
            'plan_kind' => $plan->kind->value,
            'credits' => $plan->credits,
            'duration_days' => $plan->duration_days,
            'daily_interest_limit' => $plan->daily_interest_limit,
            'amount_minor' => $plan->price_minor,
            'currency' => $plan->currency,
        ]);
    }

    public function gateway(PaymentGateway $gateway, ?string $ref = null): static
    {
        return $this->state(fn (): array => [
            'gateway' => $gateway->value,
            'gateway_ref' => $ref,
        ]);
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status->value]);
    }
}
