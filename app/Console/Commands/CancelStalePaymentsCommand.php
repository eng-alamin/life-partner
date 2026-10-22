<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payment\CancelStalePayments;
use Illuminate\Console\Command;

class CancelStalePaymentsCommand extends Command
{
    protected $signature = 'payments:cancel-stale';

    protected $description = 'Cancel checkouts that were started but never submitted';

    public function handle(CancelStalePayments $cancel): int
    {
        $count = $cancel();

        $this->info("Cancelled {$count} stale payment(s).");

        return self::SUCCESS;
    }
}
