<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

/**
 * Shows one published content page. The slug comes from the route definition (a fixed
 * list) and is checked again here. An unknown or unpublished page gives a plain 404.
 */
final class PageController extends Controller
{
    public function show(string $slug): View
    {
        abort_unless(in_array($slug, Page::SLUGS, true), 404);

        $page = Page::query()->where('slug', $slug)->where('is_published', true)->first();

        abort_if($page === null, 404);

        return view('pages.show', ['page' => $page]);
    }
}
