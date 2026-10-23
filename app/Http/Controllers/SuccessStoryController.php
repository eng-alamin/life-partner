<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SuccessStory;
use Illuminate\Contracts\View\View;

/**
 * The public success stories. Only published stories with a valid consent are ever read:
 * a draft, a withdrawn story and an unknown code all give the same plain 404.
 * Nothing about an account or a biodata is loaded, because a story holds no such link.
 */
final class SuccessStoryController extends Controller
{
    private const PER_PAGE = 9;

    public function index(): View
    {
        $stories = SuccessStory::query()
            ->public()
            ->select(['id', 'code', 'couple_label', 'title_bn', 'title_en', 'body_bn', 'body_en', 'published_at'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE);

        return view('stories.index', ['stories' => $stories]);
    }

    public function show(string $code): View
    {
        $story = SuccessStory::query()
            ->public()
            ->where('code', $code)
            ->select(['id', 'code', 'couple_label', 'title_bn', 'title_en', 'body_bn', 'body_en', 'published_at'])
            ->first();

        abort_if($story === null, 404);

        return view('stories.show', ['story' => $story]);
    }
}
