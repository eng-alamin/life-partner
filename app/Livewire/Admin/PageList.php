<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The fixed list of content pages for the admin. Pages can not be added or deleted here;
 * only their words and published state change (see PageForm and SavePage).
 */
#[Layout('layouts::admin')]
class PageList extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Page::class);
    }

    public function render()
    {
        Gate::authorize('viewAny', Page::class);

        $saved = Page::query()
            ->select(['slug', 'title_bn', 'title_en', 'is_published', 'updated_at'])
            ->get()
            ->keyBy('slug');

        $rows = collect(Page::SLUGS)->map(function (string $slug) use ($saved): array {
            /** @var Page|null $page */
            $page = $saved->get($slug);

            return [
                'slug' => $slug,
                'label' => __('admin_pages.slugs.'.$slug),
                'title' => $page?->title(),
                'published' => $page?->is_published ?? false,
                'saved' => $page !== null,
                'updated' => $page?->updated_at?->diffForHumans(),
            ];
        })->all();

        return view('livewire.admin.page-list', ['rows' => $rows])->title(__('admin_pages.list.title'));
    }
}
