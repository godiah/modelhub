<?php

namespace App\Console\Commands;

use App\Services\Payments\EarningsService;
use Illuminate\Console\Command;

class ReleaseEarnings extends Command
{
    protected $signature = 'earnings:release';

    protected $description = 'Make sellers\' earnings available to withdraw once their hold period has ended';

    public function handle(EarningsService $earnings): int
    {
        $this->info("Released {$earnings->releaseDue()} sales.");

        return self::SUCCESS;
    }
}
