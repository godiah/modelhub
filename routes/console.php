<?php

use App\Console\Commands\CheckPayouts;
use App\Console\Commands\DeactivateExpiredJobs;
use App\Console\Commands\ExpirePayments;
use App\Console\Commands\PurgeTicketEvidence;
use App\Console\Commands\ReleaseEarnings;
use App\Console\Commands\ReleaseEscrow;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The assistant's evidence on a support ticket does not outlive the ticket for long. Registered with the Schedule facade, the form Laravel 11 and later
// read from this file (a returned closure, as below, is not picked up: see the note under it).
Illuminate\Support\Facades\Schedule::command(PurgeTicketEvidence::class)->dailyAt('03:30')->withoutOverlapping();

// NOTE (found 2026-10-07): the closure returned below is NOT read by Laravel 12, so the tasks in it are not scheduled (`php artisan schedule:list` shows only the
// one registered above). They need registering with the Schedule facade or `withSchedule()` in bootstrap/app.php. Left as it was: switching them on starts money jobs.
return function (Schedule $schedule) {
    $schedule->command(DeactivateExpiredJobs::class)->hourly();

    // Unanswered M-Pesa prompts are given up on, after a last check in case the money arrived
    $schedule->command(ExpirePayments::class)->everyFiveMinutes()->withoutOverlapping();

    // Sale earnings leave the hold and become withdrawable; withdrawals being sent are checked with the gateway
    $schedule->command(ReleaseEarnings::class)->hourly()->withoutOverlapping();
    $schedule->command(CheckPayouts::class)->everyFiveMinutes()->withoutOverlapping();

    // A safety net for job escrow: anything approved but not yet released is released
    $schedule->command(ReleaseEscrow::class)->hourly()->withoutOverlapping();
};
