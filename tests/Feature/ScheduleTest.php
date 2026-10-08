<?php

use App\Console\Commands\CheckPayouts;
use App\Console\Commands\DeactivateExpiredJobs;
use App\Console\Commands\ExpirePayments;
use App\Console\Commands\PurgeTicketEvidence;
use App\Console\Commands\ReleaseEarnings;
use App\Console\Commands\ReleaseEscrow;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

/*
 * The recurring jobs are really scheduled. They once sat in a closure returned from routes/console.php, which Laravel 12 never reads, so none of them
 * ran: holds were never released, unanswered M-Pesa prompts never expired and stuck withdrawals were never re-checked, and nothing failed to say so.
 * This holds the list: a job dropped from routes/console.php, put back in a form Laravel ignores, or allowed to overlap itself fails the build.
 */

/** @return array<string, Event> the scheduled events keyed by the artisan command they run */
function scheduledJobs(): array
{
    Artisan::call('list'); // boots the console kernel, which loads routes/console.php

    return collect(app(Schedule::class)->events())
        ->mapWithKeys(fn (Event $event) => [trim(str_replace([PHP_BINARY, "'artisan'", 'artisan'], '', $event->command), " '\"") => $event])
        ->all();
}

it('schedules every recurring job, and no others', function () {
    expect(array_keys(scheduledJobs()))->toEqualCanonicalizing([
        'jobs:deactivate-expired',
        'payments:expire',
        'earnings:release',
        'payouts:check',
        'escrow:release',
        'support:purge-ticket-evidence',
    ]);
});

it('runs each job as often as its work needs', function (string $command, string $cron) {
    expect(scheduledJobs()[$command]->expression)->toBe($cron);
})->with([
    ['jobs:deactivate-expired', '0 * * * *'],
    ['payments:expire', '*/5 * * * *'],
    ['earnings:release', '0 * * * *'],
    ['payouts:check', '*/5 * * * *'],
    ['escrow:release', '0 * * * *'],
    ['support:purge-ticket-evidence', '30 3 * * *'],
]);

it('never lets a job that moves or checks money overlap itself', function (string $command) {
    expect(scheduledJobs()[$command]->withoutOverlapping)->toBeTrue();
})->with(['payments:expire', 'earnings:release', 'payouts:check', 'escrow:release', 'support:purge-ticket-evidence']);

it('schedules commands that exist', function () {
    foreach ([DeactivateExpiredJobs::class, ExpirePayments::class, ReleaseEarnings::class, CheckPayouts::class, ReleaseEscrow::class, PurgeTicketEvidence::class] as $class) {
        expect(array_keys(Artisan::all()))->toContain((new $class)->getName());
    }
});

it('does not keep the old closure that Laravel ignores', function () {
    expect(file_get_contents(base_path('routes/console.php')))->not->toMatch('/^return function \(Schedule/m');
});
