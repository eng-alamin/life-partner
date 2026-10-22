<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Delete notifications that were read a long time ago';

    public function handle(): int
    {
        $days = max(1, (int) config('lifepartner.notifications.keep_read_days'));

        $deleted = DatabaseNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays($days))
            ->delete();

        if ($deleted > 0) {
            // Only a count is logged, never any content.
            activity('notification')
                ->withProperties(['deleted' => $deleted])
                ->log('notifications.pruned');
        }

        $this->info("Deleted {$deleted} old read notification(s).");

        return self::SUCCESS;
    }
}
