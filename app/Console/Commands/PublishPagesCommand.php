<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;

/**
 * Publishes content pages from the command line, for example after the owner has
 * read the texts: php artisan pages:publish contact privacy terms refund
 *
 * Only the published flag changes; the words are never touched. A page needs a
 * Bangla title and body to be published (same rule as the admin form). The activity
 * log gets the slug only, never the text.
 */
class PublishPagesCommand extends Command
{
    protected $signature = 'pages:publish {slugs* : One or more page slugs, or "all" for every unpublished page}';

    protected $description = 'Publish content pages (about, how-it-works, faq, contact, privacy, terms, refund)';

    public function handle(): int
    {
        $slugs = (array) $this->argument('slugs');

        if ($slugs === ['all']) {
            $slugs = Page::query()->where('is_published', false)->pluck('slug')->all();
        }

        $failed = false;

        foreach ($slugs as $slug) {
            $failed = ! $this->publish((string) $slug) || $failed;
        }

        Page::forgetPublishedSlugs();

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function publish(string $slug): bool
    {
        if (! in_array($slug, Page::SLUGS, true)) {
            $this->error("Unknown page: {$slug}");

            return false;
        }

        $page = Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            $this->error("{$slug}: the page does not exist yet. Run: php artisan db:seed --class=PageSeeder");

            return false;
        }

        if (trim($page->title_bn) === '' || trim($page->body_bn) === '') {
            $this->error("{$slug}: needs a Bangla title and body before it can be published.");

            return false;
        }

        if ($page->is_published) {
            $this->line("{$slug}: already published.");

            return true;
        }

        $page->forceFill(['is_published' => true])->save();

        activity('page')
            ->performedOn($page)
            ->withProperties(['slug' => $slug, 'fields' => ['is_published'], 'is_published' => true])
            ->log('page.updated');

        $this->info("{$slug}: published.");

        return true;
    }
}
