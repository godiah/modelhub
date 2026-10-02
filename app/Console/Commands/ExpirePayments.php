<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;

class ExpirePayments extends Command
{
    protected $signature = 'payments:expire';

    protected $description = 'Give up on M-Pesa prompts nobody answered, after one last check with the gateway in case the money did arrive';

    public function handle(PaymentService $payments): int
    {
        $this->info("Checked {$payments->expireStale()} unanswered payments.");

        return self::SUCCESS;
    }
}
