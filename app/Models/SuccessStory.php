<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StoryConsentForm;
use App\Enums\StoryConsentGiver;
use App\Enums\StoryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A success story shown on the public page. Nothing is mass-assignable: rows are written only by
 * SaveSuccessStory and ChangeStoryState with forceFill(). A story has NO link to a user or a biodata.
 * It is public only while it is published AND its consent was not withdrawn.
 */
class SuccessStory extends Model
{
    use HasFactory;

    public const PUBLISHED_CACHE_KEY = 'stories.has-published';

    /** @var list<string> */
    protected $fillable = [];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::creating(function (SuccessStory $story): void {
            if (blank($story->code)) {
                $story->code = Str::lower(Str::random(24));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => StoryStatus::class,
            'consent_given_by' => StoryConsentGiver::class,
            'consent_form' => StoryConsentForm::class,
            'consent_given_at' => 'date',
            'consent_withdrawn_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** What visitors may see: published, with a publish date, and no withdrawn consent. */
    public function scopePublic(Builder $query): Builder
    {
        return $query
            ->where('status', StoryStatus::Published->value)
            ->whereNull('consent_withdrawn_at')
            ->whereNotNull('published_at');
    }

    /** Whether any story is public. Cached for ten minutes; used to show the menu link only when it leads somewhere. */
    public static function hasPublished(): bool
    {
        return (bool) Cache::remember(
            self::PUBLISHED_CACHE_KEY,
            600,
            fn (): bool => self::query()->public()->exists(),
        );
    }

    public static function forgetPublishedCache(): void
    {
        Cache::forget(self::PUBLISHED_CACHE_KEY);
    }

    public function title(): string
    {
        return $this->inLocale((string) $this->title_bn, (string) $this->title_en);
    }

    /** The text in the reader's language; falls back to the other language when that one is empty. */
    public function body(): string
    {
        return $this->inLocale((string) $this->body_bn, (string) $this->body_en);
    }

    /** True when the people consented and did not withdraw. */
    public function hasValidConsent(): bool
    {
        return $this->consent_given_by !== null
            && $this->consent_form !== null
            && $this->consent_given_at !== null
            && $this->consent_withdrawn_at === null;
    }

    /** given | missing | withdrawn, for the admin list. */
    public function consentState(): string
    {
        if ($this->consent_withdrawn_at !== null) {
            return 'withdrawn';
        }

        return $this->hasValidConsent() ? 'given' : 'missing';
    }

    /**
     * Why this story can not be public now: a key under admin_stories.errors, or null when it can.
     * The one rule that decides publishing, used by every action.
     */
    public function publishProblem(): ?string
    {
        if ($this->consent_given_by === null || $this->consent_form === null || $this->consent_given_at === null) {
            return 'consent_missing';
        }

        if ($this->consent_withdrawn_at !== null) {
            return 'consent_withdrawn';
        }

        $hasBangla = trim((string) $this->title_bn) !== '' && trim((string) $this->body_bn) !== '';
        $hasEnglish = trim((string) $this->title_en) !== '' && trim((string) $this->body_en) !== '';

        return $hasBangla || $hasEnglish ? null : 'text_missing';
    }

    private function inLocale(string $bn, string $en): string
    {
        [$first, $second] = app()->getLocale() === 'en' ? [$en, $bn] : [$bn, $en];

        return trim($first) !== '' ? $first : $second;
    }
}
