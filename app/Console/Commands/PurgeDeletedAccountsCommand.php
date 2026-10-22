<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Account\PurgeDeletedAccounts;
use Illuminate\Console\Command;

class PurgeDeletedAccountsCommand extends Command
{
    protected $signature = 'accounts:purge-deleted';

    protected $description = 'Purge accounts whose 14-day deletion wait is over';

    public function handle(PurgeDeletedAccounts $purge): int
    {
        $counts = $purge();

        $this->info("Purged {$counts['purged']}, paused {$counts['paused']}, skipped {$counts['skipped']}, failed {$counts['failed']}.");

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
