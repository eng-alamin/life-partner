<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A static content page (about, FAQ, terms ...). The list of slugs is fixed in code,
 * so staff can change the words but can never create an address of their own.
 * Nothing is mass-assignable; SavePage fills every column.
 */
class Page extends Model
{
    /** The only pages that exist. Each one has a public route named pages.{slug}. */
    public const SLUGS = ['about', 'how-it-works', 'faq', 'contact', 'privacy', 'terms', 'refund'];

    public const PUBLISHED_CACHE_KEY = 'pages.published-slugs';

    /** @var list<string> */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Slugs of published pages, cached for ten minutes. Used by menus and the footer. */
    public static function publishedSlugs(): array
    {
        /** @var list<string> $slugs */
        $slugs = Cache::remember(
            self::PUBLISHED_CACHE_KEY,
            600,
            fn (): array => self::query()->where('is_published', true)->pluck('slug')->all(),
        );

        return $slugs;
    }

    public static function forgetPublishedSlugs(): void
    {
        Cache::forget(self::PUBLISHED_CACHE_KEY);
    }

    public function title(): string
    {
        return $this->inLocale($this->title_bn, $this->title_en);
    }

    /** The body in the reader's language; falls back to the other language when that one is empty. */
    public function body(): string
    {
        return $this->inLocale($this->body_bn, $this->body_en);
    }

    private function inLocale(string $bn, string $en): string
    {
        [$first, $second] = app()->getLocale() === 'en' ? [$en, $bn] : [$bn, $en];

        return trim($first) !== '' ? $first : $second;
    }
}
