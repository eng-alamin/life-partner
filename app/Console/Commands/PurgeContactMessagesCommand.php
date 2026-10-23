<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Contact\PurgeOldContactMessages;
use Illuminate\Console\Command;

class PurgeContactMessagesCommand extends Command
{
    protected $signature = 'contact:purge-old';

    protected $description = 'Delete contact form messages that are older than the retention period (180 days)';

    public function handle(PurgeOldContactMessages $purge): int
    {
        $deleted = $purge();

        $this->info("Deleted {$deleted} old contact message(s).");

        return self::SUCCESS;
    }
}
