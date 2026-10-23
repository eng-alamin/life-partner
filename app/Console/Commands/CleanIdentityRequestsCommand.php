<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Identity\CleanIdentityRequests;
use Illuminate\Console\Command;

class CleanIdentityRequestsCommand extends Command
{
    protected $signature = 'identity:cleanup';

    protected $description = 'Close old identity requests and delete leftover identity pictures';

    public function handle(CleanIdentityRequests $clean): int
    {
        $result = $clean();

        $this->info("Closed {$result['expired']} old request(s), deleted {$result['files']} leftover file(s).");

        return self::SUCCESS;
    }
}
