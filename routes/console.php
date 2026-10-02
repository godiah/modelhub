<?php

use App\Console\Commands\CheckPayouts;
use App\Console\Commands\DeactivateExpiredJobs;
use App\Console\Commands\ExpirePayments;
use App\Console\Commands\ReleaseEarnings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

return function (Schedule $schedule) {
    $schedule->command(DeactivateExpiredJobs::class)->hourly();

    // Unanswered M-Pesa prompts are given up on, after a last check in case the money arrived
    $schedule->command(ExpirePayments::class)->everyFiveMinutes()->withoutOverlapping();

    // Sale earnings leave the hold and become withdrawable; withdrawals being sent are checked with the gateway
    $schedule->command(ReleaseEarnings::class)->hourly()->withoutOverlapping();
    $schedule->command(CheckPayouts::class)->everyFiveMinutes()->withoutOverlapping();
};
