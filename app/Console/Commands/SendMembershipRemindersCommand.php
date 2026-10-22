<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Subscription\SendMembershipReminders;
use Illuminate\Console\Command;

class SendMembershipRemindersCommand extends Command
{
    protected $signature = 'subscriptions:remind';

    protected $description = 'Tell members whose paid period ends soon (each period once)';

    public function handle(SendMembershipReminders $remind): int
    {
        $count = $remind();

        $this->info("Sent {$count} membership reminder(s).");

        return self::SUCCESS;
    }
}
