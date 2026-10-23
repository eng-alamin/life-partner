<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Story\ChangeStoryState;
use App\Actions\Story\SaveSuccessStory;
use App\Enums\StoryConsentForm;
use App\Enums\StoryConsentGiver;
use App\Models\SuccessStory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Writes or edits one success story. The story code is locked: the browser can not point the
 * form at another story. Every value is untrusted and checked again in SaveSuccessStory and
 * ChangeStoryState, which also check the policy.
 */
#[Layout('layouts::admin')]
class StoryForm extends Component
{
    #[Locked]
    public ?string $storyCode = null;

    public string $couple_label = '';

    public string $title_bn = '';

    public string $title_en = '';

    public string $body_bn = '';

    public string $body_en = '';

    public string $consent_given_by = '';

    public string $consent_given_at = '';

    public string $consent_form = '';

    public string $consent_note = '';

    public function mount(?string $story = null): void
    {
        if ($story === null) {
            Gate::authorize('create', SuccessStory::class);

            return;
        }

        $model = SuccessStory::query()->where('code', $story)->first();

        abort_if($model === null, 404);

        Gate::authorize('update', $model);

        $this->storyCode = $model->code;
        $this->couple_label = (string) $model->couple_label;
        $this->title_bn = (string) $model->title_bn;
        $this->title_en = (string) $model->title_en;
        $this->body_bn = (string) $model->body_bn;
        $this->body_en = (string) $model->body_en;
        $this->consent_given_by = $model->consent_given_by?->value ?? '';
        $this->consent_given_at = $model->consent_given_at?->format('Y-m-d') ?? '';
        $this->consent_form = $model->consent_form?->value ?? '';
        $this->consent_note = (string) ($model->consent_note ?? '');
    }

    public function save(SaveSuccessStory $save): void
    {
        $this->begin();

        $story = $save(auth()->user(), $this->storyCode, $this->input());

        session()->flash('status', __('admin_stories.flash.saved'));

        $this->storyCode === null
            ? $this->redirectRoute('admin.stories.edit', $story->code)
            : $this->redirectRoute('admin.stories');
    }

    /** Saves what is on the form first, so the public page shows exactly what staff just read. */
    public function publish(SaveSuccessStory $save, ChangeStoryState $state): void
    {
        $this->begin();
        abort_if($this->storyCode === null, 404);

        $story = $save(auth()->user(), $this->storyCode, $this->input());
        $state->publish(auth()->user(), $story->code);

        $this->done('published');
    }

    public function unpublish(ChangeStoryState $state): void
    {
        $this->begin();
        abort_if($this->storyCode === null, 404);

        $state->unpublish(auth()->user(), $this->storyCode);

        $this->done('unpublished');
    }

    public function withdraw(ChangeStoryState $state): void
    {
        $this->begin();
        abort_if($this->storyCode === null, 404);

        $state->withdrawConsent(auth()->user(), $this->storyCode);

        $this->done('withdrawn');
    }

    public function delete(ChangeStoryState $state): void
    {
        $this->begin();
        abort_if($this->storyCode === null, 404);

        $state->delete(auth()->user(), $this->storyCode);

        $this->done('deleted');
    }

    public function render()
    {
        $story = $this->storyCode !== null
            ? SuccessStory::query()->where('code', $this->storyCode)->firstOrFail()
            : null;

        if ($story !== null) {
            Gate::authorize('update', $story);
        }

        return view('livewire.admin.story-form', [
            'story' => $story,
            'consentState' => $story?->consentState(),
            'publicUrl' => $story !== null ? route('stories.show', $story->code) : null,
            'givers' => StoryConsentGiver::cases(),
            'forms' => StoryConsentForm::cases(),
        ])->title($story === null ? __('admin_stories.form.title_new') : __('admin_stories.form.title_edit'));
    }

    /** @return array<string, string> */
    private function input(): array
    {
        return $this->only([
            'couple_label', 'title_bn', 'title_en', 'body_bn', 'body_en',
            'consent_given_by', 'consent_given_at', 'consent_form', 'consent_note',
        ]);
    }

    private function begin(): void
    {
        $this->resetErrorBag();

        $key = 'story-admin:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function done(string $outcome): void
    {
        session()->flash('status', __('admin_stories.flash.'.$outcome));
        $this->redirectRoute('admin.stories');
    }
}
