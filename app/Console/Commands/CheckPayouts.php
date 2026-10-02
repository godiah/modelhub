<?php

namespace App\Console\Commands;

use App\Services\Payments\PayoutService;
use Illuminate\Console\Command;

class CheckPayouts extends Command
{
    protected $signature = 'payouts:check';

    protected $description = 'Ask the gateway about withdrawals being sent, for results whose callback has not arrived';

    public function handle(PayoutService $payouts): int
    {
        $this->info("Checked {$payouts->checkProcessing()} withdrawals.");

        return self::SUCCESS;
    }
}
