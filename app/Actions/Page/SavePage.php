<?php

declare(strict_types=1);

namespace App\Actions\Page;

use App\Models\Page;
use App\Models\User;
use App\Support\Digits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Saves the words of one fixed page. Everything is checked here on the server.
 *
 * - The slug must be one of Page::SLUGS. A page that does not exist yet is created on first save.
 * - Titles are required (one line, 120 characters at most). Bodies are plain text, 15,000 characters at most.
 * - A page can only be published when the Bangla title and body are filled. The English body may stay
 *   empty: visitors then see the Bangla text.
 * - The activity log records the slug, the names of the fields that changed and the published flag.
 *   It never holds the text itself.
 */
final class SavePage
{
    public const BODY_MAX = 15000;

    public const TITLE_MAX = 120;

    private const TRACKED = ['title_bn', 'title_en', 'body_bn', 'body_en', 'is_published'];

    /** @param  array<string, mixed>  $input  title_bn, title_en, body_bn, body_en, is_published */
    public function __invoke(User $staff, string $slug, array $input): Page
    {
        if (! in_array($slug, Page::SLUGS, true)) {
            abort(404);
        }

        Gate::forUser($staff)->authorize('update', new Page);

        $data = $this->validated($input);

        $page = DB::transaction(function () use ($staff, $slug, $data): Page {
            $page = Page::query()->where('slug', $slug)->lockForUpdate()->first();
            $isNew = $page === null;
            $page ??= new Page;

            $changed = [];

            foreach (self::TRACKED as $field) {
                if ($isNew || $page->getAttribute($field) !== $data[$field]) {
                    $changed[] = $field;
                }
            }

            if (! $isNew && $changed === []) {
                return $page;
            }

            $page->forceFill([...$data, 'slug' => $slug, 'updated_by' => $staff->getKey()])->save();

            activity('page')
                ->causedBy($staff)
                ->performedOn($page)
                ->withProperties(['slug' => $slug, 'fields' => $changed, 'is_published' => $data['is_published']])
                ->log($isNew ? 'page.created' : 'page.updated');

            return $page;
        });

        Page::forgetPublishedSlugs();

        return $page;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{title_bn: string, title_en: string, body_bn: string, body_en: string, is_published: bool}
     */
    private function validated(array $input): array
    {
        $data = [];

        foreach (['title_bn', 'title_en'] as $key) {
            $value = $this->line($input[$key] ?? '');

            if ($value === '' || mb_strlen($value) > self::TITLE_MAX) {
                throw ValidationException::withMessages([$key => __('admin_pages.errors.title_invalid', ['max' => Digits::localize(self::TITLE_MAX)])]);
            }

            $data[$key] = $value;
        }

        foreach (['body_bn', 'body_en'] as $key) {
            $value = $this->block($input[$key] ?? '');

            if ($value === null || mb_strlen($value) > self::BODY_MAX) {
                throw ValidationException::withMessages([$key => __('admin_pages.errors.body_invalid', ['max' => Digits::localize(self::BODY_MAX)])]);
            }

            $data[$key] = $value;
        }

        $data['is_published'] = filter_var($input['is_published'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($data['is_published'] && $data['body_bn'] === '') {
            throw ValidationException::withMessages(['body_bn' => __('admin_pages.errors.publish_needs_bangla')]);
        }

        return $data;
    }

    /** One line: repeated spaces collapsed, no control characters (the zero width joiners that Bangla needs are kept). Anything that is not text becomes empty. */
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
