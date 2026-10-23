<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payment\ReconcileBkashPayments;
use App\Services\Payment\BkashClient;
use Illuminate\Console\Command;

class ReconcileBkashPaymentsCommand extends Command
{
    protected $signature = 'bkash:reconcile';

    protected $description = 'Ask bKash about payments whose result was unclear and settle the completed ones';

    public function handle(BkashClient $client, ReconcileBkashPayments $reconcile): int
    {
        if (! $client->isEnabled()) {
            $this->info('The bKash API is switched off. Nothing to do.');

            return self::SUCCESS;
        }

        $counts = $reconcile();

        $this->info("Checked {$counts['checked']}, paid {$counts['paid']}, needs attention {$counts['attention']}, still waiting {$counts['waiting']}, errors {$counts['errors']}.");

        return self::SUCCESS;
    }
}
