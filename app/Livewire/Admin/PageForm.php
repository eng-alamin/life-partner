<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Page\SavePage;
use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Edits the words of one fixed page. The slug is locked: the browser can not point the
 * form at another page, and SavePage checks it against Page::SLUGS again.
 */
#[Layout('layouts::admin')]
class PageForm extends Component
{
    #[Locked]
    public string $slug = '';

    public string $title_bn = '';

    public string $title_en = '';

    public string $body_bn = '';

    public string $body_en = '';

    public bool $is_published = false;

    public function mount(string $slug): void
    {
        abort_unless(in_array($slug, Page::SLUGS, true), 404);

        Gate::authorize('viewAny', Page::class);

        $this->slug = $slug;

        $page = Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            $this->title_bn = __('admin_pages.slugs.'.$slug, [], 'bn');
            $this->title_en = __('admin_pages.slugs.'.$slug, [], 'en');

            return;
        }

        $this->title_bn = $page->title_bn;
        $this->title_en = $page->title_en;
        $this->body_bn = $page->body_bn;
        $this->body_en = $page->body_en;
        $this->is_published = $page->is_published;
    }

    public function save(SavePage $save): void
    {
        Gate::authorize('viewAny', Page::class);

        $this->resetErrorBag();
        $this->throttle();

        $save(auth()->user(), $this->slug, $this->only(['title_bn', 'title_en', 'body_bn', 'body_en', 'is_published']));

        session()->flash('status', __('admin_pages.flash.saved'));
        $this->redirectRoute('admin.content');
    }

    public function render()
    {
        return view('livewire.admin.page-form', [
            'label' => __('admin_pages.slugs.'.$this->slug),
            'isLegal' => in_array($this->slug, ['privacy', 'terms', 'refund'], true),
            'needsDetails' => $this->slug === 'contact',
            'publicUrl' => route('pages.'.$this->slug),
        ])->title(__('admin_pages.form.title'));
    }

    private function throttle(): void
    {
        $key = 'page-admin:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
