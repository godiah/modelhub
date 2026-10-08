<?php

use App\Console\Commands\CheckPayouts;
use App\Console\Commands\DeactivateExpiredJobs;
use App\Console\Commands\ExpirePayments;
use App\Console\Commands\PurgeTicketEvidence;
use App\Console\Commands\ReleaseEarnings;
use App\Console\Commands\ReleaseEscrow;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * The recurring jobs. Registered with the Schedule facade, the form Laravel 11 and later read from this file. (Until 2026-10-08 the money jobs below
 * were in a closure RETURNED from this file, which Laravel 12 never reads: `php artisan schedule:list` showed none of them and they had never run
 * since the upgrade, so holds were not released, unanswered M-Pesa prompts never expired and stuck withdrawals were never re-checked.)
 *
 * tests/Feature/ScheduleTest.php holds this list: a job dropped from here, or one that overlaps itself, fails the build.
 */

Schedule::command(DeactivateExpiredJobs::class)->hourly();

// Unanswered M-Pesa prompts are given up on, after a last check in case the money arrived
Schedule::command(ExpirePayments::class)->everyFiveMinutes()->withoutOverlapping();

// Sale earnings leave the hold and become withdrawable; withdrawals being sent are checked with the gateway
Schedule::command(ReleaseEarnings::class)->hourly()->withoutOverlapping();
Schedule::command(CheckPayouts::class)->everyFiveMinutes()->withoutOverlapping();

// A safety net for job escrow: anything approved but not yet released is released
Schedule::command(ReleaseEscrow::class)->hourly()->withoutOverlapping();

// The assistant's evidence on a support ticket does not outlive the ticket for long
Schedule::command(PurgeTicketEvidence::class)->dailyAt('03:30')->withoutOverlapping();
