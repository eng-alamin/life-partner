<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Interest\ExpireInterests;
use Illuminate\Console\Command;

class ExpireInterestsCommand extends Command
{
    protected $signature = 'interests:expire';

    protected $description = 'Expire pending interest requests whose time has run out';

    public function handle(ExpireInterests $expire): int
    {
        $count = $expire();

        $this->info("Expired {$count} interest request(s).");

        return self::SUCCESS;
    }
}
