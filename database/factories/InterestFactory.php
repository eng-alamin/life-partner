<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BiodataType;
use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interest>
 */
class InterestFactory extends Factory
{
    protected $model = Interest::class;

    public function definition(): array
    {
        return [
            'sender_biodata_id' => Biodata::factory()->approved()->ofType(BiodataType::Groom),
            'receiver_biodata_id' => Biodata::factory()->approved()->ofType(BiodataType::Bride),
            'status' => InterestStatus::Pending->value,
            'guardian_status' => GuardianApprovalStatus::NotRequired->value,
            'expires_at' => now()->addDays((int) config('lifepartner.interests.expiry_days', 14)),
        ];
    }

    public function between(Biodata $sender, Biodata $receiver): static
    {
        return $this->state(fn () => [
            'sender_biodata_id' => $sender->getKey(),
            'receiver_biodata_id' => $receiver->getKey(),
        ]);
    }

    public function withStatus(InterestStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status->value,
            'responded_at' => $status === InterestStatus::Pending ? null : now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->withStatus(InterestStatus::Accepted);
    }

    public function declined(): static
    {
        return $this->withStatus(InterestStatus::Declined);
    }

    public function cancelled(): static
    {
        return $this->withStatus(InterestStatus::Cancelled);
    }

    public function expired(): static
    {
        return $this->withStatus(InterestStatus::Expired)->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
