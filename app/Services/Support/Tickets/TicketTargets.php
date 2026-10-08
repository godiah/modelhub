<?php

namespace App\Services\Support\Tickets;

use App\Enums\SupportTicketSeverity;

/**
 * The response targets in words a member can read ("within 4 business hours (weekdays, 8am to 6pm EAT)"), from the same config the clocks use. They are
 * PROVISIONAL (O-11), so they are always phrased as an aim, never as a promise.
 */
final class TicketTargets
{
    /** "within 4 business hours" */
    public static function firstReply(SupportTicketSeverity $severity): string
    {
        return __('within :amount', ['amount' => self::firstReplyAmount($severity)]);
    }

    /** "4 business hours": the same target without the "within", for a sentence that supplies its own words. */
    public static function firstReplyAmount(SupportTicketSeverity $severity): string
    {
        $minutes = (int) config("support.tickets.targets.{$severity->value}.first_response");

        return self::amount($minutes);
    }

    /** The resolution target, for staff ("1 business day", or "Best effort" when there is none). */
    public static function resolutionAmount(SupportTicketSeverity $severity): string
    {
        $minutes = config("support.tickets.targets.{$severity->value}.resolution");

        return $minutes === null ? __('Best effort') : self::amount((int) $minutes);
    }

    private static function amount(int $minutes): string
    {
        $day = self::dayMinutes();

        if ($minutes >= $day && $minutes % $day === 0) {
            $days = intdiv($minutes, $day);

            return trans_choice(':count business day|:count business days', $days, ['count' => $days]);
        }

        $hours = max(1, (int) round($minutes / 60));

        return trans_choice(':count business hour|:count business hours', $hours, ['count' => $hours]);
    }

    /** "weekdays, 8am to 6pm EAT" */
    public static function hours(): string
    {
        $days = (array) config('support.tickets.business_days');
        $when = $days === [1, 2, 3, 4, 5] ? __('weekdays') : implode(', ', array_map(fn ($d) => now()->startOfWeek()->addDays($d - 1)->format('D'), $days));
        $format = fn (string $t) => ltrim(date('ga', strtotime($t)), '0');

        return $when.', '.$format((string) config('support.tickets.business_hours.start')).' '.__('to').' '.$format((string) config('support.tickets.business_hours.end')).' EAT';
    }

    /** The one sentence a member is shown when they file or follow a request. */
    public static function sentence(SupportTicketSeverity $severity): string
    {
        return __('We aim to reply :when (:hours). We cannot promise a time yet, but a person will read it.', ['when' => self::firstReply($severity), 'hours' => self::hours()]);
    }

    private static function dayMinutes(): int
    {
        $start = strtotime((string) config('support.tickets.business_hours.start'));
        $end = strtotime((string) config('support.tickets.business_hours.end'));

        return max(60, (int) (($end - $start) / 60));
    }
}
