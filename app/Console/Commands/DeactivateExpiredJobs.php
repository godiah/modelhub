<?php

namespace App\Console\Commands;

use App\Models\ModelJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeactivateExpiredJobs extends Command
{
    protected $signature = 'jobs:deactivate-expired';

    protected $description = 'Deactivate jobs with passed deadlines';

    public function handle()
    {
        ModelJob::where('is_active', true)
            ->where('no_deadline', false)
            ->where('deadline', '<=', Carbon::now())
            ->update(['is_active' => false]);

        $this->info('Expired jobs deactivated successfully');
    }
}
