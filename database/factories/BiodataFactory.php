<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BiodataStatus;
use App\Enums\BiodataType;
use App\Enums\MaritalStatus;
use App\Models\Biodata;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Laravel factories build models "unguarded", so user_id and status (which are
 * not mass-assignable in application code) can be set here for tests.
 *
 * @extends Factory<Biodata>
 */
class BiodataFactory extends Factory
{
    protected $model = Biodata::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => BiodataStatus::Draft->value,
            'type' => fake()->randomElement(BiodataType::cases())->value,
            'marital_status' => MaritalStatus::NeverMarried->value,
            'birth_date' => fake()->dateTimeBetween('-35 years', '-22 years')->format('Y-m-d'),
            'height_cm' => fake()->numberBetween(150, 185),
            'present_country' => 'BD',
            'profession' => 'Teacher',
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->getKey()]);
    }

    /** Approved and visible in search (reviewed_at orders the results). */
    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => BiodataStatus::Approved->value,
            'reviewed_at' => now(),
        ]);
    }

    public function ofType(BiodataType $type): static
    {
        return $this->state(fn () => ['type' => $type->value]);
    }

    public function withStatus(BiodataStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }
}
