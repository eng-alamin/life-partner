<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IdentityDocumentType;
use App\Enums\IdentityRejectionReason;
use App\Enums\IdentityStatus;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentityVerification>
 */
class IdentityVerificationFactory extends Factory
{
    protected $model = IdentityVerification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => IdentityStatus::Pending->value,
            'document_type' => IdentityDocumentType::Nid->value,
            'front_path' => fn (): string => 'test/'.fake()->unique()->lexify('????????????????').'.jpg',
            'back_path' => null,
            // Open while pending: the same number as the member, so only one request can be open.
            'open_key' => fn (array $attributes): int => (int) $attributes['user_id'],
            'submitted_at' => now(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->getKey()]);
    }

    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn (): array => [
            'status' => IdentityStatus::Approved->value,
            'open_key' => null,
            'front_path' => null,
            'back_path' => null,
            'files_deleted_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->getKey(),
        ]);
    }

    public function rejected(IdentityRejectionReason $reason = IdentityRejectionReason::Unreadable, ?User $reviewer = null): static
    {
        return $this->state(fn (): array => [
            'status' => IdentityStatus::Rejected->value,
            'open_key' => null,
            'front_path' => null,
            'back_path' => null,
            'files_deleted_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->getKey(),
            'rejection_reason' => $reason->value,
        ]);
    }
}
