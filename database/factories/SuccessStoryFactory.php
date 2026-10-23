<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SuccessStory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SuccessStory> */
class SuccessStoryFactory extends Factory
{
    protected $model = SuccessStory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'couple_label' => 'A. and F.',
            'title_bn' => 'আলহামদুলিল্লাহ, আমাদের বিয়ে হয়েছে',
            'title_en' => 'Alhamdulillah, we got married',
            'body_bn' => 'আমরা এখানে পরিবারের মাধ্যমে পরিচিত হয়েছি।',
            'body_en' => 'We were introduced through our families here.',
            'status' => 'draft',
            'consent_given_by' => null,
            'consent_given_at' => null,
            'consent_form' => null,
            'consent_note' => null,
            'consent_withdrawn_at' => null,
            'published_at' => null,
        ];
    }

    public function withConsent(): static
    {
        return $this->state(fn (): array => [
            'consent_given_by' => 'both',
            'consent_given_at' => now()->subDays(3)->toDateString(),
            'consent_form' => 'written',
        ]);
    }

    public function published(): static
    {
        return $this->withConsent()->state(fn (): array => [
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
            'consent_withdrawn_at' => now(),
            'published_at' => null,
        ]);
    }
}
