<?php

declare(strict_types=1);

namespace App\Actions\Story;

use App\Enums\StoryStatus;
use App\Models\SuccessStory;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Publish, unpublish, withdraw consent of, or delete one success story.
 *
 * - The policy is checked before and again under a row lock.
 * - Publishing follows one rule (SuccessStory::publishProblem): consent recorded and not withdrawn,
 *   and a title with a text in at least one language.
 * - Withdrawing consent makes the story a draft at once, so it leaves the public page.
 * - The activity log holds the story code and the action name only.
 */
final class ChangeStoryState
{
    public function publish(User $staff, string $code): SuccessStory
    {
        return $this->transition($staff, $code, 'story.published', function (SuccessStory $story): void {
            $problem = $story->publishProblem();

            if ($problem !== null) {
                throw ValidationException::withMessages(['form' => __('admin_stories.errors.'.$problem)]);
            }

            $story->forceFill([
                'status' => StoryStatus::Published->value,
                'published_at' => $story->published_at ?? now(),
            ]);
        });
    }

    public function unpublish(User $staff, string $code): SuccessStory
    {
        return $this->transition($staff, $code, 'story.unpublished', function (SuccessStory $story): void {
            $story->forceFill([
                'status' => StoryStatus::Draft->value,
                'published_at' => null,
            ]);
        });
    }

    public function withdrawConsent(User $staff, string $code): SuccessStory
    {
        return $this->transition($staff, $code, 'story.consent_withdrawn', function (SuccessStory $story): void {
            if ($story->consent_given_at === null) {
                throw ValidationException::withMessages(['form' => __('admin_stories.errors.nothing_to_withdraw')]);
            }

            $story->forceFill([
                'consent_withdrawn_at' => now(),
                'status' => StoryStatus::Draft->value,
                'published_at' => null,
            ]);
        });
    }

    public function delete(User $staff, string $code): void
    {
        $early = SuccessStory::query()->where('code', $code)->firstOrFail();
        Gate::forUser($staff)->authorize('delete', $early);

        DB::transaction(function () use ($staff, $code): void {
            $story = SuccessStory::query()->where('code', $code)->lockForUpdate()->firstOrFail();

            Gate::forUser($staff)->authorize('delete', $story);

            $story->delete();

            activity('story')
                ->causedBy($staff)
                ->withProperties(['story_code' => $code])
                ->log('story.deleted');
        });

        SuccessStory::forgetPublishedCache();
    }

    /** @param  Closure(SuccessStory): void  $change */
    private function transition(User $staff, string $code, string $event, Closure $change): SuccessStory
    {
        $early = SuccessStory::query()->where('code', $code)->firstOrFail();
        Gate::forUser($staff)->authorize('update', $early);

        $story = DB::transaction(function () use ($staff, $code, $event, $change): SuccessStory {
            $story = SuccessStory::query()->where('code', $code)->lockForUpdate()->firstOrFail();

            Gate::forUser($staff)->authorize('update', $story);

            $change($story);

            $story->forceFill(['updated_by' => $staff->getKey()])->save();

            activity('story')
                ->causedBy($staff)
                ->withProperties(['story_code' => $story->code])
                ->log($event);

            return $story;
        });

        SuccessStory::forgetPublishedCache();

        return $story;
    }
}
