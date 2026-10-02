<?php

namespace App\Console\Commands;

use App\Models\JobEngagement;
use App\Services\Payments\EscrowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseEscrow extends Command
{
    protected $signature = 'escrow:release';

    protected $description = 'Release escrow for approved deliverables that were approved but not yet released (a safety net: approving normally releases at once)';

    public function handle(EscrowService $escrow): int
    {
        $released = 0;

        // Funded jobs that still hold money and have something approved
        JobEngagement::where('escrow_minor', '>', 0)->whereColumn('escrow_minor', '>', DB::raw('released_net_minor + released_fee_minor + refunded_minor'))
            ->whereHas('deliverables', fn ($q) => $q->where('status', 'approved'))->each(function (JobEngagement $engagement) use ($escrow, &$released) {
                $released += $escrow->releaseApproved($engagement) > 0 ? 1 : 0;
            });

        $this->info("Released escrow on {$released} jobs.");

        return self::SUCCESS;
    }
}
