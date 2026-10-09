<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketSeverity;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Services\Support\Tickets\BusinessHours;
use App\Services\Support\Tickets\TicketTargets;
use Carbon\CarbonImmutable;

/**
 * Service levels: how quickly each kind of request should get a first reply, when the clock runs, how urgency is decided, and where the team stands
 * right now. The numbers are read from config/support.php, the same place the clocks are worked out from, so what this page says is what the
 * clocks do and what members are told. They are provisional (O-11), and changing them is a change to that file, not a form.
 */
class AdminSupportServiceLevelsController extends Controller
{
    public function show()
    {
        $levels = collect(array_reverse(SupportTicketSeverity::cases()))->map(fn (SupportTicketSeverity $severity) => [
            'severity' => $severity,
            'first' => TicketTargets::firstReplyAmount($severity),
            'resolution' => TicketTargets::resolutionAmount($severity),
            'members_read' => TicketTargets::sentence($severity),
            'first_minutes' => (int) config("support.tickets.targets.{$severity->value}.first_response"),
        ]);

        $clock = BusinessHours::fromConfig();
        $now = CarbonImmutable::now(config('support.tickets.timezone'));
        $open = $clock->isOpenAt($now);
        $days = (array) config('support.tickets.business_days');
        $week = collect(range(1, 7))->map(fn (int $day) => ['name' => $now->startOfWeek()->addDays($day - 1)->format('D'), 'open' => in_array($day, $days, true), 'today' => $now->dayOfWeekIso === $day])->all();

        $overdue = SupportTicket::overdue()->count();
        $waiting = SupportTicket::needingStaff()->count();

        return view('admin.support.service-levels', [
            'levels' => $levels,
            'hours' => TicketTargets::hours(),
            'week' => $week,
            'openNow' => $open,
            'opensAt' => $open ? null : $clock->nextOpening($now),
            'dayHours' => [self::clockLabel((string) config('support.tickets.business_hours.start')), self::clockLabel((string) config('support.tickets.business_hours.end'))],
            'staleHours' => (int) config('payments.payout_stale_hours'),
            'urgentWords' => (array) config('support.tickets.urgent_words'),
            'status' => [
                'waiting' => $waiting,
                'overdue' => $overdue,
                'soon' => SupportTicket::dueSoon(120)->count(),
                'unassigned' => SupportTicket::needingStaff()->whereNull('assignee_id')->count(),
            ],
            'team' => Staff::permission('manage support tickets')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'avatar'])
                ->map(fn (Staff $person) => [
                    'person' => $person,
                    'name' => $person->name,
                    'open' => SupportTicket::active()->where('assignee_id', $person->id)->count(),
                    'overdue' => SupportTicket::overdue()->where('assignee_id', $person->id)->count(),
                ])->all(),
        ]);
    }

    private static function clockLabel(string $time): string
    {
        return ltrim(date('g:i A', strtotime($time)), '0');
    }
}
