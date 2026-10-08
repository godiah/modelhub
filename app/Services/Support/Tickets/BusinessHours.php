<?php

namespace App\Services\Support\Tickets;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Counting time the way the response targets are promised: in business hours (days and hours from config/support.php), in the support team's
 * time zone. A ticket filed on Friday evening is "due" in business minutes from Monday morning.
 */
final class BusinessHours
{
    /** @param  list<int>  $days  ISO weekdays, 1 = Monday */
    public function __construct(
        private readonly array $days,
        private readonly string $start,
        private readonly string $end,
        private readonly string $timezone,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (array) config('support.tickets.business_days'),
            (string) config('support.tickets.business_hours.start'),
            (string) config('support.tickets.business_hours.end'),
            (string) config('support.tickets.timezone'),
        );
    }

    /** The moment that is `$minutes` of business time after `$from`. */
    public function addMinutes(CarbonInterface $from, int $minutes): CarbonImmutable
    {
        $cursor = $this->nextOpening(CarbonImmutable::instance($from)->setTimezone($this->timezone));
        $left = $minutes;

        while (true) {
            $close = $this->at($cursor, $this->end);
            $available = (int) $cursor->diffInMinutes($close, false);

            if ($left <= $available) {
                return $cursor->addMinutes($left);
            }

            $left -= $available;
            $cursor = $this->nextOpening($cursor->addDay()->startOfDay());
        }
    }

    /** `$at` itself if it is within business hours, otherwise the next time the team is in. */
    public function nextOpening(CarbonImmutable $at): CarbonImmutable
    {
        for ($i = 0; $i < 14; $i++) {
            $day = $at->addDays($i);

            if (! in_array($day->isoWeekday(), $this->days, true)) {
                continue;
            }

            $open = $this->at($day, $this->start);
            $close = $this->at($day, $this->end);

            if ($i === 0 && $at->lt($open)) {
                return $open;
            }
            if ($i === 0 && $at->lt($close)) {
                return $at;
            }
            if ($i > 0) {
                return $open;
            }
        }

        return $at;
    }

    public function isOpenAt(CarbonInterface $moment): bool
    {
        $local = CarbonImmutable::instance($moment)->setTimezone($this->timezone);

        return in_array($local->isoWeekday(), $this->days, true) && $local->gte($this->at($local, $this->start)) && $local->lt($this->at($local, $this->end));
    }

    private function at(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $day->setTime($hour, $minute);
    }
}
