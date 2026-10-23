<?php

declare(strict_types=1);

namespace App\Actions\Story;

use App\Enums\StoryConsentForm;
use App\Enums\StoryConsentGiver;
use App\Models\SuccessStory;
use App\Models\User;
use App\Support\Digits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Creates or changes a success story (the words and the consent record). Everything is checked here.
 *
 * - The label is first names or initials only: no digits, no "@", no address-like characters.
 * - The titles and texts must not hold an email address or a long number (a safety net against
 *   pasted contact details; staff must still read what they publish).
 * - The consent fields come all together or not at all. The date can not be in the future.
 * - A story that was withdrawn stays blocked until a LATER consent date is recorded.
 * - A published story must stay publishable: its consent can not be emptied while it is public.
 * - Status is never changed here (see ChangeStoryState).
 * - The activity log holds the story code and the names of the changed fields, never the words.
 */
final class SaveSuccessStory
{
    public const LABEL_MAX = 60;

    public const TITLE_MAX = 120;

    public const BODY_MAX = 8000;

    public const NOTE_MAX = 200;

    private const TRACKED = [
        'couple_label', 'title_bn', 'title_en', 'body_bn', 'body_en',
        'consent_given_by', 'consent_given_at', 'consent_form', 'consent_note',
    ];

    /**
     * @param  array<string, mixed>  $input  couple_label, title_bn, title_en, body_bn, body_en, consent_given_by, consent_given_at, consent_form, consent_note
     */
    public function __invoke(User $staff, ?string $code, array $input): SuccessStory
    {
        $existing = $code !== null ? SuccessStory::query()->where('code', $code)->firstOrFail() : null;

        Gate::forUser($staff)->authorize($existing !== null ? 'update' : 'create', $existing ?? SuccessStory::class);

        $data = $this->validated($input);

        $story = DB::transaction(function () use ($staff, $code, $data): SuccessStory {
            $story = $code !== null
                ? SuccessStory::query()->where('code', $code)->lockForUpdate()->firstOrFail()
                : new SuccessStory;

            Gate::forUser($staff)->authorize($story->exists ? 'update' : 'create', $story->exists ? $story : SuccessStory::class);

            $isNew = ! $story->exists;

            $story->forceFill($data);

            // A newer consent date lifts an earlier withdrawal. The same or an older date does not.
            if ($story->consent_withdrawn_at !== null
                && $data['consent_given_at'] !== null
                && $data['consent_given_at'] > $story->consent_withdrawn_at->toDateString()) {
                $story->forceFill(['consent_withdrawn_at' => null]);
            }

            if (! $isNew && $story->status->value === 'published') {
                $problem = $story->publishProblem();

                if ($problem !== null) {
                    throw ValidationException::withMessages(['form' => __('admin_stories.errors.'.$problem)]);
                }
            }

            $changed = array_values(array_filter(
                self::TRACKED,
                fn (string $field): bool => $isNew || $story->isDirty($field),
            ));

            if (! $isNew && $changed === [] && ! $story->isDirty('consent_withdrawn_at')) {
                return $story;
            }

            if ($isNew) {
                $story->forceFill(['created_by' => $staff->getKey()]);
            }

            $story->forceFill(['updated_by' => $staff->getKey()])->save();

            activity('story')
                ->causedBy($staff)
                ->withProperties(['story_code' => $story->code, 'fields' => $changed])
                ->log($isNew ? 'story.created' : 'story.updated');

            return $story;
        });

        SuccessStory::forgetPublishedCache();

        return $story;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    private function validated(array $input): array
    {
        $label = $this->line($input['couple_label'] ?? '');

        if ($label === '' || mb_strlen($label) > self::LABEL_MAX || preg_match('/[\p{N}@\/\\\\:]/u', $label) === 1) {
            throw ValidationException::withMessages(['couple_label' => __('admin_stories.errors.label_invalid', ['max' => Digits::localize(self::LABEL_MAX)])]);
        }

        $data = ['couple_label' => $label];

        foreach (['title_bn', 'title_en'] as $key) {
            $value = $this->line($input[$key] ?? '');

            if (mb_strlen($value) > self::TITLE_MAX) {
                throw ValidationException::withMessages([$key => __('admin_stories.errors.title_invalid', ['max' => Digits::localize(self::TITLE_MAX)])]);
            }

            $this->refuseContactDetails($key, $value);
            $data[$key] = $value;
        }

        foreach (['body_bn', 'body_en'] as $key) {
            $value = $this->block($input[$key] ?? '');

            if ($value === null || mb_strlen($value) > self::BODY_MAX) {
                throw ValidationException::withMessages([$key => __('admin_stories.errors.body_invalid', ['max' => Digits::localize(self::BODY_MAX)])]);
            }

            $this->refuseContactDetails($key, $value);
            $data[$key] = $value;
        }

        return $data + $this->consent($input);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    private function consent(array $input): array
    {
        $by = $this->line($input['consent_given_by'] ?? '');
        $form = $this->line($input['consent_form'] ?? '');
        $date = $this->line($input['consent_given_at'] ?? '');
        $note = $this->line($input['consent_note'] ?? '');

        $filled = count(array_filter([$by, $form, $date], fn (string $value): bool => $value !== ''));

        if ($filled > 0 && $filled < 3) {
            throw ValidationException::withMessages(['consent_given_by' => __('admin_stories.errors.consent_partial')]);
        }

        if ($filled === 3) {
            if (StoryConsentGiver::tryFrom($by) === null) {
                throw ValidationException::withMessages(['consent_given_by' => __('admin_stories.errors.consent_value_invalid')]);
            }

            if (StoryConsentForm::tryFrom($form) === null) {
                throw ValidationException::withMessages(['consent_form' => __('admin_stories.errors.consent_value_invalid')]);
            }

            if (! $this->isPastOrToday($date)) {
                throw ValidationException::withMessages(['consent_given_at' => __('admin_stories.errors.consent_date_invalid')]);
            }
        }

        if (mb_strlen($note) > self::NOTE_MAX) {
            throw ValidationException::withMessages(['consent_note' => __('admin_stories.errors.note_invalid', ['max' => Digits::localize(self::NOTE_MAX)])]);
        }

        $this->refuseContactDetails('consent_note', $note);

        return [
            'consent_given_by' => $by !== '' ? $by : null,
            'consent_given_at' => $date !== '' ? $date : null,
            'consent_form' => $form !== '' ? $form : null,
            'consent_note' => $note !== '' ? $note : null,
        ];
    }

    private function isPastOrToday(string $date): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) !== 1) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) && $date <= now()->toDateString();
    }

    /** An email address or a long run of digits is refused. A safety net, not a guarantee. */
    private function refuseContactDetails(string $field, string $text): void
    {
        $email = preg_match('/[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}.\-]+\.\p{L}{2,}/u', $text) === 1;
        $number = preg_match('/\p{Nd}{7,}|\+\p{Nd}/u', $text) === 1;

        if ($email || $number) {
            throw ValidationException::withMessages([$field => __('admin_stories.errors.contact_found')]);
        }
    }

    /** One line: repeated spaces collapsed, no control characters (zero width joiners that Bangla needs are kept). */
    private function line(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $clean = preg_replace('/\p{Cc}+/u', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', (string) $clean) ?? '');
    }

    /** Many lines: line breaks are kept, other control characters are removed. Null when the text is not valid UTF-8. */
    private function block(mixed $value): ?string
    {
        if (! is_string($value)) {
            return '';
        }

        $text = str_replace(["\r\n", "\r"], "\n", $value);
        $clean = preg_replace('/[^\P{Cc}\n]/u', '', $text);

        return $clean === null ? null : trim($clean);
    }
}
