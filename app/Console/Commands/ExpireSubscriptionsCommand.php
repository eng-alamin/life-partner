<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Subscription\ExpireSubscriptions;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark membership periods whose end date has passed as expired';

    public function handle(ExpireSubscriptions $expire): int
    {
        $count = $expire();

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
